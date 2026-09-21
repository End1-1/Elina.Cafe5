#include "appwebsocket.h"
#include <QDebug>
#include <QTimer>
#include <QJsonDocument>

QString AppWebSocket::mHost;
AppWebSocket* AppWebSocket::instance = nullptr;

AppWebSocket::AppWebSocket(QObject *parent)
    : QObject{parent},
      mConnectionState(disconnected)
{
    mSocket = new QWebSocket();
    connect(mSocket, &QWebSocket::connected, this, &AppWebSocket::connectedToServer);
    connect(mSocket, &QWebSocket::disconnected, this, &AppWebSocket::disconnectedFromServer);
    connect(mSocket, &QWebSocket::errorOccurred, this, &AppWebSocket::socketError);
    connect(mSocket, &QWebSocket::textMessageReceived, this, &AppWebSocket::textMessageReceived);
    connect(mSocket, &QWebSocket::binaryMessageReceived, this, &AppWebSocket::binaryMessageReceived);
    connectToServer();
    auto *timer = new QTimer(this);
    connect(timer, &QTimer::timeout, this, &AppWebSocket::pingServer);
    timer->start(10000);
}

AppWebSocket::~AppWebSocket()
{
    mSocket->deleteLater();
}

void AppWebSocket::initInstance()
{
    instance = new AppWebSocket();
}

void AppWebSocket::reconnect(const QString &host, const QString &key, const QString &username, const QString &password)
{
    if(!instance) {
        initInstance();
    }

    instance->mRegistered = false;
    instance->mHost = host;
    instance->mServerKey = key;
    instance->mUsername = username;
    instance->mPassword = password;
    qDebug() << "AppWebSocket::reconnect host" << host << "key" << key << "user" << username;

    if(instance->mSocket->state() != QAbstractSocket::UnconnectedState) {
        instance->mSocket->abort();
    }

    instance->mConnectionState = disconnected;
    instance->connectToServer();
}

void AppWebSocket::sendMessage(const QString &message)
{
    qDebug() << "sending message" << message;
    mSocket->sendTextMessage(message);
}

void AppWebSocket::sendBinaryMessage(const QByteArray &ba)
{
    qDebug() << "sending binary message" << ba.size() << "bytes" << ba;
    mSocket->sendBinaryMessage(ba);
}

void AppWebSocket::sendMessage(const QJsonObject &json)
{
    sendMessage(QJsonDocument(json).toJson(QJsonDocument::Compact));
}

bool AppWebSocket::isRegistered() const
{
    return mRegistered;
}

void AppWebSocket::connectToServer()
{
    qDebug() << "connecting to server" << mHost;

    if(mHost.isEmpty()) {
        qDebug() << "Host is empty";
        return;
    }

    if(mConnectionState == connecting) {
        return;
    }

    if(mSocket->state() == QAbstractSocket::ConnectedState
        || mSocket->state() == QAbstractSocket::ConnectingState) {
        return;
    }

    mConnectionState = connecting;
    mRegistered = false;
    emit socketConnecting();
    QUrl url(mHost);
    mSocket->open(url);
}

void AppWebSocket::pingServer()
{
    if(mConnectionState == connected) {
        sendMessage("ping");
    } else {
        connectToServer();
    }
}

void AppWebSocket::connectedToServer()
{
    qDebug() << "connected to server";
    mConnectionState = connected;
    mRegistered = false;
    emit socketConnected();

    if(!mServerKey.isEmpty()) {
        QJsonObject jo = {
            {"command", "register_socket"},
            {"key", mServerKey},
            {"username", mUsername},
            {"password", mPassword}
        };
        sendMessage(jo);
    }
}

void AppWebSocket::disconnectedFromServer()
{
    qDebug() << "disconnected from websocket server";
    mConnectionState = disconnected;
    mRegistered = false;
    emit socketDisconnected();
}

void AppWebSocket::socketError(QAbstractSocket::SocketError error)
{
    Q_UNUSED(error);
    qDebug() << "websocket error" << mSocket->errorString();
    mRegistered = false;
    mConnectionState = disconnected;
    emit socketDisconnected();
    QTimer::singleShot(3000, this, &AppWebSocket::connectToServer);
}

void AppWebSocket::dispatchJsonMessage(const QJsonObject &jo)
{
    if(jo.value("status").toInt() == 1 && !mServerKey.isEmpty()) {
        mRegistered = true;
        qDebug() << "AppWebSocket registered with key" << mServerKey;
    }

    if(jo.contains("errorCode") && jo.value("errorCode").toInt() != 0) {
        qWarning() << "AppWebSocket error" << jo;
    }

    emit bMessageReceived(jo);
}

void AppWebSocket::textMessageReceived(const QString &message)
{
    qDebug() << "websocket message" << message;

    if(message.toLower() == "pong") {
        return;
    }

    emit messageReceived(message);

    QJsonParseError err;
    QJsonDocument doc = QJsonDocument::fromJson(message.toUtf8(), &err);

    if(err.error == QJsonParseError::NoError && doc.isObject()) {
        dispatchJsonMessage(doc.object());
    }
}

void AppWebSocket::binaryMessageReceived(const QByteArray &data)
{
    qDebug() << "binary message" << data;
    QJsonParseError err;
    QJsonDocument doc = QJsonDocument::fromJson(data, &err);

    if(err.error != QJsonParseError::NoError) {
        qWarning() << "JSON error:" << err.errorString();
        return;
    }

    if(doc.isObject()) {
        dispatchJsonMessage(doc.object());
    }
}
