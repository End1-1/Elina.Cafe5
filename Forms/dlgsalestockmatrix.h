#ifndef DLGSALESTOCKMATRIX_H
#define DLGSALESTOCKMATRIX_H

#include "c5dialog.h"
#include <QJsonArray>

namespace Ui
{
class DlgSaleStockMatrix;
}

class DlgSaleStockMatrix : public C5Dialog
{
    Q_OBJECT

public:
    explicit DlgSaleStockMatrix(C5User *user, const QJsonArray &rows, int targetStoreId, const QString &orderNumber, QWidget *parent = nullptr);

    ~DlgSaleStockMatrix();

    static bool open(C5User *user, QWidget *parent, const QJsonArray &rows, int targetStoreId, const QString &orderNumber = QString());

    bool executeMovements(QString &err);

    void notifyHold(int sourceStoreId, const QJsonArray &items);

private slots:
    void on_btnClose_clicked();

    void on_btnOK_clicked();

private:
    void fillTable(const QJsonObject &response);

    Ui::DlgSaleStockMatrix *ui;
    int fTargetStoreId = 0;
    QString fOrderNumber;
    bool fDataLoaded = false;
};

#endif // DLGSALESTOCKMATRIX_H
