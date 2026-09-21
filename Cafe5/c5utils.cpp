#include "c5utils.h"
#include <QDateTime>
#include <QImage>
#include <QImageReader>
#include <QObject>
#include <QPainter>
#include <QPixmap>
#include <QProcessEnvironment>

C5Utils __c5utils;

C5Utils::C5Utils()
{
}
QString password(const QString &value)
{
    QByteArray ba(value.toUtf8());
    return QCryptographicHash::hash(ba, QCryptographicHash::Md5).toHex();
}

QString hostusername()
{
    QProcessEnvironment env = QProcessEnvironment::systemEnvironment();
#ifdef Q_OS_WIN
    return env.value("USERNAME");
#else
    return env.value("USER");
#endif
}

QString columnNumberToLetter(int n)
{
    QString letter = "";

    while(n > 0) {
        n--;  // Уменьшаем на 1, чтобы учитывать нумерацию с A
        letter = char(n % 26 + 'A') + letter;
        n /= 26;
    }

    return letter;
}

bool loadImageAsRgb(const QString &fileName, QPixmap &pm, QString *error)
{
    QImageReader reader(fileName);
    reader.setAutoTransform(true);
    QImage img = reader.read();

    if(img.isNull()) {
        img.load(fileName);
    }

    if(img.isNull()) {
        if(error) {
            *error = reader.errorString();

            if(error->isEmpty()) {
                *error = QObject::tr("Unknown error");
            }
        }

        return false;
    }

    if(img.hasAlphaChannel()) {
        QImage bg(img.size(), QImage::Format_RGB32);
        bg.fill(Qt::white);
        QPainter p(&bg);
        p.drawImage(0, 0, img);
        p.end();
        img = bg;
    }

    pm = QPixmap::fromImage(img);

    if(pm.isNull()) {
        if(error) {
            *error = QObject::tr("Failed to convert image");
        }

        return false;
    }

    return true;
}
