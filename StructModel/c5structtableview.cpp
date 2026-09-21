#include "c5structtableview.h"
#include <QHeaderView>
#include <QJsonDocument>
#include <QDebug>
#include "appwebsocket.h"
#include "c5message.h"
#include "store_doc_status.h"
#include "store_doc_type.h"
#include "struct_goods_group.h"
#include "struct_goods_item.h"
#include "struct_partner.h"
#include "struct_storage_item.h"
#include "ui_c5structtableview.h"

C5StructTableView::C5StructTableView(C5User *user)
    : C5Dialog(user), ui(new Ui::C5StructTableView)
{
    ui->setupUi(this);
    ui->tbl->setSortingEnabled(true);
    ui->tbl->setSelectionBehavior(QAbstractItemView::SelectRows);
    ui->tbl->setSelectionMode(QAbstractItemView::SingleSelection);
    ui->tbl->horizontalHeader()->setStretchLastSection(true);
    mSearchTimer = new QTimer(this);
    mSearchTimer->setSingleShot(true);
    connect(mSearchTimer, &QTimer::timeout,
            this, &C5StructTableView::sendSearchRequest);
    connect(AppWebSocket::instance, &AppWebSocket::bMessageReceived,
    this, [this](const QJsonObject & jo) {
        if(jo.contains("errorCode") && jo.value("errorCode").toInt() != 0) {
            if(mLastRequestId.isEmpty() || jo.value("requestId").toString() == mLastRequestId) {
                qWarning() << "Search error" << jo;
                C5Message::error(jo.value("errorMessage").toString());
            }

            return;
        }

        if(!jo.contains("result")) {
            return;
        }

        if(!mLastRequestId.isEmpty() && jo.contains("requestId")
            && jo.value("requestId").toString() != mLastRequestId) {
            return;
        }

        QJsonArray arr = jo["result"].toArray();
        qDebug() << "Search result" << mSearchEngine << "count" << arr.size();

        if(mSearchEngine == SelectorName<StorageItem>::value) {
            handleSearchResult<StorageItem>(arr, static_cast<C5StructModel<StorageItem>*>(ui->tbl->model()));
        } else if(mSearchEngine == SelectorName<GoodsItem>::value) {
            handleSearchResult<GoodsItem>(arr, static_cast<C5StructModel<GoodsItem>*>(ui->tbl->model()));
        } else if(mSearchEngine == SelectorName<PartnerItem>::value) {
            // Service5 sends f_contact; JsonParser reads f_name
            QVector<PartnerItem> data;
            data.reserve(arr.size());

            for(const QJsonValue &v : arr) {
                QJsonObject row = v.toObject();

                if(!row.contains("f_name") && row.contains("f_contact")) {
                    row["f_name"] = row["f_contact"];
                }

                data.append(JsonParser<PartnerItem>::fromJson(row));
            }

            static_cast<C5StructModel<PartnerItem>*>(ui->tbl->model())->setData(std::move(data));
        } else if (mSearchEngine == SelectorName<StoreDocStatusItem>::value) {
            handleSearchResult<StoreDocStatusItem>(arr, static_cast<C5StructModel<StoreDocStatusItem> *>(ui->tbl->model()));
        } else if (mSearchEngine == SelectorName<StoreDocTypeItem>::value) {
            handleSearchResult<StoreDocTypeItem>(arr, static_cast<C5StructModel<StoreDocTypeItem> *>(ui->tbl->model()));
        } else if (mSearchEngine == SelectorName<GoodsGroupItem>::value) {
            handleSearchResult<GoodsGroupItem>(arr, static_cast<C5StructModel<GoodsGroupItem> *>(ui->tbl->model()));
        } else {
            Q_ASSERT_X(false, Q_FUNC_INFO, qPrintable(QString("Unknown search engine: %1").arg(mSearchEngine)));
        }

        ui->tbl->resizeColumnsToContents();
    });
}

C5StructTableView::~C5StructTableView() { delete ui; }

QTableView* C5StructTableView::tableView()
{
    return ui->tbl;
}

QMap<QString, QString> C5StructTableView::selectorTitles()
{
    return {{SelectorName<GoodsItem>::value, QObject::tr("Goods")},
            {SelectorName<GoodsGroupItem>::value, QObject::tr("Group")},
            {SelectorName<StorageItem>::value, QObject::tr("Storage")},
            {SelectorName<PartnerItem>::value, QObject::tr("Partner")}};
}

void C5StructTableView::on_leSearchText_textChanged(const QString &arg1)
{
    if(arg1.isEmpty()) {
        if(!mEmptySearch) {
            return;
        }
    }

    mSearchTimer->start(mSearchDelay);
}

void C5StructTableView::sendSearchRequest()
{
    if(!AppWebSocket::instance || !AppWebSocket::instance->isRegistered()) {
        qWarning() << "Search deferred: websocket not registered yet";
        mSearchTimer->start(500);
        return;
    }

    QString text = ui->leSearchText->text().trimmed();
    QJsonObject jo;
    jo["command"] = mSearchEngine;
    jo["lower_name"] = text.toLower();
    mLastRequestId = QUuid::createUuid().toString(QUuid::WithoutBraces);
    jo["requestId"] = mLastRequestId;
    qDebug() << "Sending request" << jo;
    AppWebSocket::instance->sendBinaryMessage(QJsonDocument(jo).toJson());
}

void C5StructTableView::on_btnSelect_clicked()
{
    bool isAcceptable = false;

    if(mSearchEngine == SelectorName<StorageItem>::value) {
        if(static_cast<C5StructModel<StorageItem>*>(ui->tbl->model())->hasSelectedData()) {
            isAcceptable = true;
        }
    } else if(mSearchEngine == SelectorName<GoodsItem>::value) {
        if(static_cast<C5StructModel<GoodsItem>*>(ui->tbl->model())->hasSelectedData()) {
            isAcceptable = true;
        }
    } else if(mSearchEngine == SelectorName<PartnerItem>::value) {
        if(static_cast<C5StructModel<PartnerItem>*>(ui->tbl->model())->hasSelectedData()) {
            isAcceptable = true;
        }
    } else if (mSearchEngine == SelectorName<StoreDocStatusItem>::value) {
        if (static_cast<C5StructModel<StoreDocStatusItem> *>(ui->tbl->model())->hasSelectedData()) {
            isAcceptable = true;
        }
    } else if (mSearchEngine == SelectorName<StoreDocTypeItem>::value) {
        if (static_cast<C5StructModel<StoreDocTypeItem> *>(ui->tbl->model())->hasSelectedData()) {
            isAcceptable = true;
        }
    } else if (mSearchEngine == SelectorName<GoodsGroupItem>::value) {
        if (static_cast<C5StructModel<GoodsGroupItem> *>(ui->tbl->model())->hasSelectedData()) {
            isAcceptable = true;
        }
    }

    if (isAcceptable) {
        accept();
    }
}

void C5StructTableView::on_tbl_doubleClicked(const QModelIndex &index)
{
    if(!index.isValid()) {
        return;
    }

    QModelIndex checkIndex = ui->tbl->model()->index(index.row(), 0);

    if(mSearchEngine == SelectorName<StorageItem>::value) {
        static_cast<C5StructModel<StorageItem>*>(ui->tbl->model())->setData(checkIndex, Qt::Checked, Qt::CheckStateRole);
    } else if(mSearchEngine == SelectorName<GoodsItem>::value) {
        static_cast<C5StructModel<GoodsItem>*>(ui->tbl->model())->setData(checkIndex, Qt::Checked, Qt::CheckStateRole);
    } else if(mSearchEngine == SelectorName<PartnerItem>::value) {
        static_cast<C5StructModel<PartnerItem>*>(ui->tbl->model())->setData(checkIndex, Qt::Checked, Qt::CheckStateRole);
    } else if (mSearchEngine == SelectorName<StoreDocStatusItem>::value) {
        static_cast<C5StructModel<StoreDocStatusItem> *>(ui->tbl->model())->setData(checkIndex, Qt::Checked, Qt::CheckStateRole);
    } else if (mSearchEngine == SelectorName<GoodsGroupItem>::value) {
        static_cast<C5StructModel<GoodsGroupItem> *>(ui->tbl->model())->setData(checkIndex, Qt::Checked, Qt::CheckStateRole);
    } else if (mSearchEngine == SelectorName<StoreDocTypeItem>::value) {
        static_cast<C5StructModel<StoreDocTypeItem> *>(ui->tbl->model())->setData(checkIndex, Qt::Checked, Qt::CheckStateRole);
    } else {
        Q_ASSERT_X(false, Q_FUNC_INFO, qPrintable(QString("Unknown search engine: %1").arg(mSearchEngine)));
    }

    on_btnSelect_clicked();
}

void C5StructTableView::on_btnCancel_clicked()
{
    reject();
}
