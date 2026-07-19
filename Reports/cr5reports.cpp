#include "cr5reports.h"
#include "c5tablemodel.h"
#include "c5tablewidget.h"
#include "c5mainwindow.h"
#include "c5storedoc.h"
#include "c5saledoc.h"
#include "cr5reportsfilter.h"
#include "c5message.h"
#include "c5config.h"
#include "c5user.h"
#include "ntablewidget.h"
#include <QJsonObject>
#include <QJsonDocument>
#include <QEventLoop>
#include <QNetworkAccessManager>
#include <QNetworkReply>
#include <QSslConfiguration>

#define REPORT_HANDLER_GIFT_CARD_TOTAL "ff64e987-a0b2-11ef-b479-022165c6dab1"

CR5Reports::CR5Reports(QWidget *parent) :
    C5ReportWidget(parent)
{
    fFilter = new CR5ReportsFilter();
    fFilterWidget = fFilter;
}

QToolBar* CR5Reports::toolBar()
{
    if(!fToolBar) {
        QList<ToolBarButtons> btn;
        btn << ToolBarButtons::tbFilter
            << ToolBarButtons::tbClearFilter
            << ToolBarButtons::tbRefresh
            << ToolBarButtons::tbExcel
            << ToolBarButtons::tbPrint;
        createStandartToolbar(btn);
    }

    return fToolBar;
}

bool CR5Reports::fetchReportConfig(int id, QJsonObject &report)
{
    if(C5Config::fDBPath.isEmpty()) {
        C5Message::error(tr("Database not configured"));
        return false;
    }

    QJsonObject jo;
    jo["call"] = "c5report";
    jo["id"] = id;
    jo["sessionkey"] = mUser ? mUser->mSessionKey : QString();

    const QString netPath = QString("%1://%2/engine/reports/list.php")
                            .arg(C5Config::fDBHost, C5Config::fDBPath);
    QNetworkAccessManager m;
    QNetworkRequest rq(netPath);
    m.setTransferTimeout(60000);
    rq.setHeader(QNetworkRequest::ContentTypeHeader, "application/json");

    if(netPath.contains("https://")) {
        QSslConfiguration sslConf = rq.sslConfiguration();
        sslConf.setPeerVerifyMode(QSslSocket::VerifyNone);
        sslConf.setProtocol(QSsl::AnyProtocol);
        rq.setSslConfiguration(sslConf);
    }

    auto *r = m.post(rq, QJsonDocument(jo).toJson());
    QEventLoop loop;
    connect(r, &QNetworkReply::finished, &loop, &QEventLoop::quit);
    loop.exec();

    if(r->error() != QNetworkReply::NoError) {
        C5Message::error(r->errorString());
        r->deleteLater();
        return false;
    }

    const QByteArray ba = r->readAll();
    r->deleteLater();
    const QJsonObject response = QJsonDocument::fromJson(ba).object();

    if(response["status"].toInt() != 1 || !response.contains("report")) {
        C5Message::error(tr("Invalid report id"));
        return false;
    }

    report = response["report"].toObject();
    return true;
}

void CR5Reports::setReport(int id)
{
    QJsonObject report;
    if(!fetchReportConfig(id, report)) {
        return;
    }

    fHandlerUuid = report["f_handler"].toString();
    fDeleteHandler = report["f_deletehandler"].toString();
    fFilterHandler = report["f_filterhandler"].toString();
    fQuery = report["f_query"].toString();
    fLabel = report["f_name"].toString();
    QStringList sumColumns = report["f_sumcolumn_indexes"].toString().split(",", Qt::SkipEmptyParts);

    for(const QString &s : std::as_const(sumColumns)) {
        fColumnsSumIndex.append(s.toInt());
    }

    if(!fFilterHandler.isEmpty()) {
        fFilter->setFields(fFilterHandler.split(",", Qt::SkipEmptyParts));
    }

    if(!fDeleteHandler.isEmpty()) {
        fToolBar->addAction(QIcon(":/delete.png"), tr("Remove"), this, SLOT(removeHandler(bool)));
    }

    fFilter->restoreFilter(this);
    buildQuery();
}

void CR5Reports::buildQuery()
{
    fSqlQuery = fQuery;
    fSqlQuery.replace("\r\n", " ");
    fSqlQuery.replace("%date1", fFilter->d1()).replace("%date2", fFilter->d2());
    fSqlQuery.replace("%filter", fFilter->replacement());
    C5ReportWidget::buildQuery();

    if(fColumnsFields.contains("color")) {
        int col = fColumnsFields.indexOf("color");

        for(int i = 0; i < fModel->rowCount(); i++) {
            fModel->setRowColor(i,  fModel->data(i, col, Qt::EditRole).toInt() == 0 ? Qt::white : Qt::red);
        }

        fTableView->setColumnWidth(col, 0);
    }
}

bool CR5Reports::tblDoubleClicked(int row, int column, const QJsonArray &values)
{
    Q_UNUSED(row);
    Q_UNUSED(column);

    if(fHandlerUuid.isEmpty()) {
        return true;
    }

    if(values.isEmpty()) {
        return true;
    }

    if(fHandlerUuid == REPORT_HANDLER_SALE_DOC_OPEN_DRAFT) {
        C5Database db;
        db[":f_id"] = values.at(0).toString();
        db.exec("select * from o_draft_sale where f_id=:f_id");

        if(!db.nextRow()) {
            C5Message::error(tr("Program error"), "draft not found by id");
            return false;
        }

        int type = db.getInt("f_saletype");

        if(type == 3) {
            QString err;
            auto *storedoc = __mainWindow->createTab<C5StoreDoc>();
            storedoc->setProperty("fromdraft", values.at(0).toString());
            storedoc->setMode(C5StoreDoc::sdInput);

            if(!storedoc->openDraft(values.at(0).toString(), err))  {
                C5Message::error(err);
                return false;
            }
        } else {
            auto *retaildoc = __mainWindow->createTab<C5SaleDoc>();
            retaildoc->setMode(type > 0 ? type : 1);

            if(!retaildoc->openDraft(values.at(0).toString())) {
                C5Message::error(tr("Failed to open draft"));
            }
        }
    } else if(fHandlerUuid == REPORT_HANDLER_GIFT_CARD_TOTAL) {
        __mainWindow->createNTab("/engine/reports/gifthistorydetails.php", "", QJsonObject{{"card", values.at(1).toString()}});
    }

    return true;
}

void CR5Reports::setSearchParameters()
{
    C5ReportWidget::setSearchParameters();
    fSqlQuery = fQuery;
    fSqlQuery.replace("%date1", fFilter->d1()).replace("%date2", fFilter->d2());
    buildQuery();
}

void CR5Reports::completeRefresh()
{
    fTableView->resizeColumnsToContents();
    fTableView->setColumnWidth(0, 0);

    for(int i = 0; i < fModel->columnCount(); i++) {
        fTableTotal->setColumnWidth(i, fTableView->columnWidth(i));
    }
}

void CR5Reports::removeHandler(bool checked)
{
    Q_UNUSED(checked);
    QModelIndexList mil = fTableView->selectionModel()->selectedIndexes();

    if(mil.isEmpty()) {
        return;
    }

    if(C5Message::question(tr("Confirm to remove selected objects")) != QDialog::Accepted) {
        return;
    }

    QSet<int> rowsSet;

    for(const QModelIndex &ml : mil) {
        rowsSet.insert(ml.row());
    }

    QList<int> rows = rowsSet.values();
    std::sort(rows.begin(), rows.end());
    QStringList queries = fDeleteHandler.split(";", Qt::SkipEmptyParts);
    C5Database db;

    for(int r : rows) {
        for(QString &q : queries) {
            db.exec(q.replace("%handler%", fModel->data(r, 0, Qt::EditRole).toString()));
        }
    }

    std::reverse(rows.begin(), rows.end());

    for(int r : rows) {
        fModel->removeRow(r);
    }
}
