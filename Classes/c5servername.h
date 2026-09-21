#ifndef C5SERVERNAME_H
#define C5SERVERNAME_H

#include <QObject>
#include <QJsonArray>
#include <QJsonObject>
#include <QTimer>

class C5ServerName : public QObject
{
    Q_OBJECT
public:
    explicit C5ServerName(const QString &server, QObject *parent = nullptr);
    ~C5ServerName();
    bool getServers();
    bool getServers(const QString &serverKey);
    bool getConnection(const QString &connectionName);

    /** Service5 mDatabases key = fkey.fdb (e.g. elina.local.cafe5), not bare fkey. */
    static QString resolveTenantKey(const QString &serverKey, const QString &settingsOrName);

    static QJsonArray mServers;
    QJsonObject mReply;
    QString mErrorString;

private:
    QString mServer;
    QString fLastTextMessage;
    QTimer mTimer;

signals:
    void messageReceived();
};

#endif // C5SERVERNAME_H
