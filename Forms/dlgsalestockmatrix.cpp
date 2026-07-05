#include "dlgsalestockmatrix.h"
#include "c5combobox.h"
#include "c5database.h"
#include "c5message.h"
#include "c5storedoc.h"
#include "c5user.h"
#include "c5utils.h"
#include "chatmessage.h"
#include "ninterface.h"
#include "ui_dlgsalestockmatrix.h"

#include <QBrush>
#include <QDateTime>
#include <QHeaderView>
#include <QJsonDocument>
#include <QMap>
#include <QTableWidgetItem>

DlgSaleStockMatrix::DlgSaleStockMatrix(C5User *user, const QJsonArray &rows, int targetStoreId, const QString &orderNumber, QWidget *parent)
    : C5Dialog(user, parent)
    , ui(new Ui::DlgSaleStockMatrix)
    , fTargetStoreId(targetStoreId)
    , fOrderNumber(orderNumber)
{
    ui->setupUi(this);
    setWindowTitle(tr("Stock by stores"));
    ui->btnOK->setEnabled(false);
    ui->tblData->horizontalHeader()->setStretchLastSection(false);
    ui->tblData->verticalHeader()->setVisible(false);

    NInterface::query("/engine/v2/officen/online-shop/stock-matrix",
                      mUser->mSessionKey,
                      this,
                      {{"rows", rows}},
                      [this](const QJsonObject &response) {
                          fillTable(response);
                      },
                      [this](const QJsonObject &jerr) {
                          C5Message::error(jerr.value("errorMessage").toString());
                          return true;
                      },
                      true,
                      30000,
                      false);
}

DlgSaleStockMatrix::~DlgSaleStockMatrix()
{
    delete ui;
}

bool DlgSaleStockMatrix::open(C5User *user, QWidget *parent, const QJsonArray &rows, int targetStoreId, const QString &orderNumber)
{
    if (rows.isEmpty()) {
        return false;
    }

    if (targetStoreId < 1) {
        C5Message::error(QObject::tr("Target store is not defined"));
        return false;
    }

    DlgSaleStockMatrix dlg(user, rows, targetStoreId, orderNumber, parent);
    return dlg.exec() == QDialog::Accepted;
}

bool DlgSaleStockMatrix::executeMovements(QString &err)
{
    const int fromCol = ui->tblData->columnCount() - 1;
    const int dataRows = ui->tblData->rowCount();
    if (dataRows < 1 || fromCol < 2) {
        return true;
    }

    QMap<int, int> storeColumns;
    for (int c = 1; c < fromCol; c++) {
        if (auto *header = ui->tblData->horizontalHeaderItem(c)) {
            storeColumns[header->data(Qt::UserRole).toInt()] = c;
        }
    }

    struct MovementLine {
        int goodsId;
        QString name;
        QString barcode;
        QString unit;
        double qty;
    };
    QMap<int, QVector<MovementLine>> linesBySource;

    for (int r = 0; r < dataRows; r++) {
        auto *item = ui->tblData->item(r, 0);
        auto *cb = qobject_cast<C5ComboBox *>(ui->tblData->cellWidget(r, fromCol));
        if (!item || !cb) {
            continue;
        }

        const int sourceStore = cb->currentData(Qt::UserRole).toInt();
        if (sourceStore < 1 || sourceStore == fTargetStoreId || !storeColumns.contains(sourceStore)) {
            continue;
        }

        const int goodsId = item->data(Qt::UserRole + 1).toInt();
        const double needed = item->data(Qt::UserRole + 2).toDouble();
        const double available = ui->tblData->getDouble(r, storeColumns.value(sourceStore));
        const double qty = qMin(available, needed);

        if (goodsId < 1 || qty < 0.000001) {
            continue;
        }

        linesBySource[sourceStore].append({
            goodsId,
            item->data(Qt::UserRole + 3).toString(),
            item->data(Qt::UserRole + 5).toString(),
            item->data(Qt::UserRole + 4).toString(),
            qty
        });
    }

    if (linesBySource.isEmpty()) {
        return true;
    }

    int docsCreated = 0;

    for (auto it = linesBySource.begin(); it != linesBySource.end(); ++it) {
        C5StoreDoc doc;
        doc.setMode(C5StoreDoc::sdMovement);
        doc.setStore(fTargetStoreId, it.key());
        doc.setReason(DOC_REASON_MOVE);
        if (fOrderNumber.isEmpty()) {
            doc.setComment(tr("Move for sale"));
        } else {
            doc.setComment(QString("%1 %2").arg(tr("Move for sale"), fOrderNumber));
        }

        QJsonArray notifyItems;
        for (const MovementLine &line : it.value()) {
            doc.addGoods(line.goodsId, line.name, line.qty, line.unit, 0, 0, "", "");
            QJsonObject item;
            item["name"] = line.name;
            item["barcode"] = line.barcode;
            item["qty"] = line.qty;
            notifyItems.append(item);
        }

        if (!doc.writeDocument(DOC_STATE_SAVED, err)) {
            return false;
        }

        notifyHold(it.key(), notifyItems);
        docsCreated++;
    }

    if (docsCreated > 0) {
        C5Message::info(tr("Created %1 movement document(s)").arg(docsCreated));
    }

    return true;
}

void DlgSaleStockMatrix::notifyHold(int sourceStoreId, const QJsonArray &items)
{
    if (items.isEmpty() || sourceStoreId < 1) {
        return;
    }

    C5Database db;
    const QString time = QDateTime::currentDateTime().toString("yyyy-MM-dd HH:mm:ss");

    for (const QJsonValue &v : items) {
        const QJsonObject item = v.toObject();
        const double qty = item.value("qty").toDouble();
        if (qty < 0.000001) {
            continue;
        }

        QJsonObject body;
        body["action"] = MSG_ONLINE_SALE;
        body["label"] = "ONLINE";
        body["time"] = time;
        body["goodsname"] = item.value("name").toString();
        const QString barcode = item.value("barcode").toString();
        if (!barcode.isEmpty()) {
            body["scancode"] = barcode;
        }
        body["qty"] = qty;
        if (!fOrderNumber.isEmpty()) {
            body["ordernumber"] = fOrderNumber;
        }

        db[":f_state"] = 0;
        db[":f_created"] = time;
        db[":f_userfrom"] = mUser->id();
        db[":f_userto"] = sourceStoreId;
        db[":f_body"] = QString::fromUtf8(QJsonDocument(body).toJson(QJsonDocument::Compact));

        if (db.insert("sys_chat", false) == 0) {
            qWarning() << "notifyHold: sys_chat insert failed:" << db.fLastError;
        }
    }
}

void DlgSaleStockMatrix::fillTable(const QJsonObject &response)
{
    const QJsonArray stores = response.value("stores").toArray();
    const QJsonArray rows = response.value("rows").toArray();
    const int storeCount = stores.size();
    const int fromCol = 1 + storeCount;

    QMap<int, QString> storeNames;
    for (const QJsonValue &storeValue : stores) {
        const QJsonObject store = storeValue.toObject();
        storeNames[store.value("f_id").toInt()] = store.value("f_name").toString();
    }

    ui->tblData->clear();
    ui->tblData->setColumnCount(2 + storeCount);
    ui->tblData->setRowCount(rows.size());

    QMap<int, int> storeColumns;
    ui->tblData->setHorizontalHeaderItem(0, new QTableWidgetItem(tr("Goods")));
    for (int i = 0; i < storeCount; i++) {
        const QJsonObject store = stores.at(i).toObject();
        const int storeId = store.value("f_id").toInt();
        storeColumns[storeId] = 1 + i;
        auto *header = new QTableWidgetItem(store.value("f_name").toString());
        header->setData(Qt::UserRole, storeId);
        ui->tblData->setHorizontalHeaderItem(1 + i, header);
    }
    ui->tblData->setHorizontalHeaderItem(fromCol, new QTableWidgetItem(tr("Move from")));

    ui->tblData->setColumnWidth(0, 280);
    for (int i = 0; i < storeCount; i++) {
        ui->tblData->setColumnWidth(1 + i, 90);
        ui->tblData->setColumnDecimals(1 + i, 3);
    }
    ui->tblData->setColumnWidth(fromCol, 180);

    for (int r = 0; r < rows.size(); r++) {
        const QJsonObject row = rows.at(r).toObject();
        const int saleRow = row.value("row").toInt();
        const int goodsId = row.value("goods_id").toInt();
        const int currentStoreId = row.value("store_id").toInt();
        QString label = QString("%1. %2").arg(saleRow + 1).arg(row.value("name").toString());
        const QString barcode = row.value("barcode").toString();
        if (!barcode.isEmpty()) {
            label += QString(" (%1)").arg(barcode);
        }

        ui->tblData->setString(r, 0, label);
        if (auto *item = ui->tblData->item(r, 0)) {
            item->setData(Qt::UserRole, saleRow);
            item->setData(Qt::UserRole + 1, goodsId);
            item->setData(Qt::UserRole + 2, row.value("qty").toDouble());
            item->setData(Qt::UserRole + 3, row.value("name").toString());
            item->setData(Qt::UserRole + 4, row.value("unit").toString());
            item->setData(Qt::UserRole + 5, barcode);
        }

        QList<int> sourceStores;
        const QJsonArray stocks = row.value("stocks").toArray();
        for (const QJsonValue &stockValue : stocks) {
            const QJsonObject stock = stockValue.toObject();
            const int storeId = stock.value("store_id").toInt();
            const double qty = stock.value("qty").toDouble();
            if (!storeColumns.contains(storeId)) {
                continue;
            }

            const int column = storeColumns.value(storeId);
            ui->tblData->setDouble(r, column, qty);
            if (storeId == currentStoreId) {
                if (auto *stockItem = ui->tblData->item(r, column)) {
                    stockItem->setBackground(QBrush(QColor(220, 235, 255)));
                }
            }

            if (qty > 0.000001 && storeId != fTargetStoreId && !sourceStores.contains(storeId)) {
                sourceStores.append(storeId);
            }
        }

        auto *storeCb = new C5ComboBox(ui->tblData);
        storeCb->addItem(tr("—"), 0);
        for (int storeId : sourceStores) {
            storeCb->addItem(storeNames.value(storeId), storeId);
        }
        storeCb->setCurrentIndex(0);
        ui->tblData->setCellWidget(r, fromCol, storeCb);
    }

    fDataLoaded = true;
    ui->btnOK->setEnabled(true);
}

void DlgSaleStockMatrix::on_btnClose_clicked()
{
    reject();
}

void DlgSaleStockMatrix::on_btnOK_clicked()
{
    if (!fDataLoaded) {
        C5Message::error(tr("Stock data is still loading"));
        return;
    }

    QString err;
    if (!executeMovements(err)) {
        if (!err.isEmpty()) {
            C5Message::error(err);
        }
        return;
    }

    accept();
}
