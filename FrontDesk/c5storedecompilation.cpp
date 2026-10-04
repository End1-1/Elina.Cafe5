#include "c5storedecompilation.h"
#include "ui_c5storedecompilation.h"
#include "c5cache.h"
#include "c5codenameselector.h"
#include "c5lineedit.h"
#include "c5message.h"
#include "c5selector.h"
#include "c5user.h"
#include "format_date.h"
#include "dict_doc_reason.h"
#include "c5utils.h"
#include "ninterface.h"
#include <QDialog>
#include <QJsonArray>
#include <QJsonObject>
#include <QUuid>

#define col1_rec 0
#define col1_goods_id 1
#define col1_goods_name 2
#define col1_barcode 3
#define col1_qty 4
#define col1_unit 5
#define col1_price 6
#define col1_total 7

#define mode_qty 1
#define mode_price 2
#define mode_total 3

static GoodsItem goodsFromSelectorRow(const QJsonArray &values)
{
    GoodsItem g;
    // checkbox column is 0
    g.id = values.at(1).toInt();
    g.groupName = values.at(2).toString();
    g.name = values.at(3).toString();
    g.unitName = values.at(4).toString();
    g.barcode = values.at(5).toString();
    g.lastInputPrice = values.at(6).toDouble();
    return g;
}

C5StoreDecompilation::C5StoreDecompilation(QWidget *parent)
    : C5Widget(parent), ui(new Ui::C5StoreDecompilation)
{
    ui->setupUi(this);
    fLabel = tr("Disassembly");
    fIconName = ":/disassembly.png";
    ui->woutputstore->selectorCallback = [this](C5CodeNameSelector *s) {
        QJsonArray values;
        if (!C5Selector::getValue(mUser, cache_goods_store, values) || values.isEmpty()) {
            return;
        }
        s->setCodeAndName(values.at(1).toInt(), values.at(2).toString());
    };
    ui->winputstore->selectorCallback = [this](C5CodeNameSelector *s) {
        QJsonArray values;
        if (!C5Selector::getValue(mUser, cache_goods_store, values) || values.isEmpty()) {
            return;
        }
        s->setCodeAndName(values.at(1).toInt(), values.at(2).toString());
    };
    ui->wcomplect->selectorCallback = [this](C5CodeNameSelector *s) {
        QJsonArray values;
        if (!C5Selector::getValue(mUser, cache_goods, values) || values.isEmpty()) {
            return;
        }
        GoodsItem g = goodsFromSelectorRow(values);
        QString name = g.name;
        if (!g.barcode.isEmpty()) {
            name += " | " + g.barcode;
        }
        s->setCodeAndName(g.id, name);
        fillComplectComposition(false);
    };
    ui->tblGoods->setColumnWidths(ui->tblGoods->columnCount(), 0, 0, 250, 150, 80, 100, 80, 80);
    ui->leQty->setValidator(new QDoubleValidator(0, 999999999, 4, ui->leQty));
    if (ui->leQty->getDouble() < 0.0001) {
        ui->leQty->setDouble(1);
    }
    mInternalId = QUuid::createUuid().toString(QUuid::WithoutBraces);
}

C5StoreDecompilation::~C5StoreDecompilation()
{
    delete ui;
}

QToolBar* C5StoreDecompilation::toolBar()
{
    if (!fToolBar) {
        fToolBar = createStandartToolbar(QList<ToolBarButtons>());
        fToolBar->addAction(QIcon(":/save.png"), tr("Save"), this, SLOT(saveDoc()));
        fToolBar->addAction(QIcon(":/draft.png"), tr("Draft"), this, SLOT(draftDoc()));
        fToolBar->addAction(QIcon(":/recycle.png"), tr("Remove"), this, SLOT(removeDocument()));
        fToolBar->addAction(QIcon(":/print.png"), tr("Print"), this, SLOT(printDoc()));
    }
    return fToolBar;
}

void C5StoreDecompilation::rowTextEdited(const QString &text)
{
    auto *le = static_cast<C5LineEdit*>(sender());
    if (!le) {
        return;
    }
    int r, c;
    if (!ui->tblGoods->findWidget(le, r, c)) {
        return;
    }
    int mode = le->property("mode").toInt();
    switch (mode) {
    case mode_qty:
        ui->tblGoods->lineEdit(r, col1_total)->setDouble(text.toDouble() * ui->tblGoods->lineEdit(r, col1_price)->getDouble());
        break;
    case mode_price:
        ui->tblGoods->lineEdit(r, col1_total)->setDouble(text.toDouble() * ui->tblGoods->lineEdit(r, col1_qty)->getDouble());
        break;
    case mode_total:
        if (ui->tblGoods->lineEdit(r, col1_qty)->getDouble() > 0.01) {
            ui->tblGoods->lineEdit(r, col1_price)->setDouble(text.toDouble() / ui->tblGoods->lineEdit(r, col1_qty)->getDouble());
        } else {
            ui->tblGoods->lineEdit(r, col1_price)->setDouble(0);
        }
        break;
    }
}

void C5StoreDecompilation::saveDoc()
{
    writeDocument(DOC_STATE_SAVED);
}

void C5StoreDecompilation::draftDoc()
{
    writeDocument(DOC_STATE_DRAFT);
}

bool C5StoreDecompilation::writeDocument(int state)
{
    QString err;
    if (ui->woutputstore->value() == 0) {
        err += tr("Output store is not defined") + "<br>";
    }
    if (ui->winputstore->value() == 0) {
        err += tr("Input store is not defined") + "<br>";
    }
    if (ui->wcomplect->value() == 0) {
        err += tr("Select complect") + "<br>";
    }
    if (ui->leQty->getDouble() < 0.0001) {
        err += tr("The quantity of complectation cannot be zero") + "<br>";
    }
    if (ui->tblGoods->rowCount() == 0) {
        err += tr("Cannot save an emtpy document") + "<br>";
    }
    for (int i = 0; i < ui->tblGoods->rowCount(); i++) {
        if (ui->tblGoods->lineEdit(i, col1_qty)->getDouble() < 0.001) {
            err += tr("Row") + " #" + QString::number(i + 1) + " " + tr("has no quantity") + "<br>";
        }
    }
    if (!err.isEmpty()) {
        C5Message::error(err);
        return false;
    }

    const int complectId = ui->wcomplect->value();
    const double complectQty = ui->leQty->getDouble();

    QJsonObject jdoc;
    QJsonObject jheader;
    jheader["f_id"] = mInternalId;
    jheader["f_userid"] = ui->leDocNum->text();
    jheader["f_state"] = state;
    jheader["f_type"] = DOC_TYPE_DECOMPLECTATION;
    jheader["f_date"] = ui->leDate->date().toString(FORMAT_DATE_TO_STR_MYSQL);
    jheader["f_operator"] = mUser->id();
    jheader["f_partner"] = QJsonValue::Null;
    jheader["f_amount"] = 0;
    jheader["f_currency"] = 1;
    jheader["f_storein"] = ui->winputstore->value();
    jheader["f_storeout"] = ui->woutputstore->value();
    jheader["f_reason"] = DOC_REASON_DISASSMLY;
    jheader["f_comment"] = ui->leComment->text();
    jheader["f_payment"] = QJsonValue::Null;
    jheader["f_paid"] = 0;
    jdoc["header"] = jheader;

    QJsonObject jbody;
    jbody["f_accepted"] = mUser->id();
    jbody["f_passed"] = mUser->id();
    jbody["f_invoice"] = "";
    jbody["f_invoicedate"] = QJsonValue::Null;
    jbody["f_reason"] = DOC_REASON_DISASSMLY;
    jbody["f_storein"] = ui->winputstore->value();
    jbody["f_storeout"] = ui->woutputstore->value();
    jbody["f_complectationcode"] = complectId;
    jbody["f_complectationqty"] = complectQty;
    jbody["f_cashdoc"] = "";
    jbody["f_basedonsale"] = 0;
    jbody["remains"] = QJsonObject();

    QJsonObject jcomplect;
    jcomplect["f_goods"] = complectId;
    jcomplect["f_qty"] = complectQty;
    jdoc["complect"] = jcomplect;

    // Complect leaves the output store.
    QJsonArray jgoods;
    QJsonObject jout;
    jout["f_goods"] = complectId;
    jout["f_qty"] = complectQty;
    jout["f_price"] = 0;
    jout["f_validto"] = QJsonValue::Null;
    jout["f_comment"] = "";
    jout["f_row"] = 0;
    jgoods.append(jout);
    jdoc["goods"] = jgoods;

    // Components enter the input store.
    QJsonArray jdisassembly;
    for (int i = 0; i < ui->tblGoods->rowCount(); i++) {
        QJsonObject jitem;
        jitem["f_goods"] = ui->tblGoods->getInteger(i, col1_goods_id);
        jitem["f_qty"] = ui->tblGoods->lineEdit(i, col1_qty)->getDouble();
        jitem["f_price"] = ui->tblGoods->lineEdit(i, col1_price)->getDouble();
        jitem["f_validto"] = QJsonValue::Null;
        jitem["f_comment"] = "";
        jitem["f_row"] = i;
        jdisassembly.append(jitem);
    }
    jdoc["goods_disassembly"] = jdisassembly;
    jdoc["add"] = QJsonArray();

    QJsonObject jpartner;
    jpartner["partner"] = 0;
    jpartner["paid"] = 0;
    jpartner["cash"] = 0;
    jdoc["partner"] = jpartner;
    jdoc["body"] = jbody;
    jdoc["session"] = QUuid::createUuid().toString(QUuid::WithoutBraces);

    NInterface::query1("/engine/v2/officen/documents/save", mUser->mSessionKey, this, jdoc,
    [this, state](const QJsonObject &jresp) {
        mDocState = state;
        if (jresp.contains("f_userid")) {
            ui->leDocNum->setText(jresp.value("f_userid").toVariant().toString());
        }
        setDocEnabled(state == DOC_STATE_DRAFT);
        C5Message::info(state == DOC_STATE_SAVED ? tr("Saved") : tr("Draft"));
    });
    return true;
}

void C5StoreDecompilation::removeDocument()
{
    if (C5Message::question(tr("Confirm to remove document")) != QDialog::Accepted) {
        return;
    }
    if (mDocState == 0) {
        ui->tblGoods->clearContents();
        ui->tblGoods->setRowCount(0);
        mBaseQtyOfComplectation.clear();
        ui->wcomplect->setCodeAndName(0, QString());
        ui->leComment->clear();
        ui->leDocNum->clear();
        ui->leQty->setDouble(1);
        mInternalId = QUuid::createUuid().toString(QUuid::WithoutBraces);
        setDocEnabled(true);
        return;
    }
    QJsonObject jo;
    jo["id"] = mInternalId;
    NInterface::query1("/engine/v2/officen/documents/remove", mUser->mSessionKey, this, jo,
    [this](const QJsonObject &) {
        mDocState = 0;
        ui->tblGoods->clearContents();
        ui->tblGoods->setRowCount(0);
        mBaseQtyOfComplectation.clear();
        ui->wcomplect->setCodeAndName(0, QString());
        ui->leComment->clear();
        ui->leDocNum->clear();
        ui->leQty->setDouble(1);
        mInternalId = QUuid::createUuid().toString(QUuid::WithoutBraces);
        setDocEnabled(true);
        C5Message::info(tr("Deleted"));
    });
}

void C5StoreDecompilation::printDoc()
{
    C5Message::info(tr("Print is not available for this document yet"));
}

void C5StoreDecompilation::setDocEnabled(bool enabled)
{
    ui->wheader->setEnabled(enabled);
    ui->wcomplectinfo->setEnabled(enabled);
    ui->tblGoods->setEnabled(enabled);
    ui->btnAddComplect->setEnabled(enabled);
    ui->btnRemove->setEnabled(enabled);
    ui->btnFillComplect->setEnabled(enabled);
    ui->leComment->setEnabled(enabled);
}

void C5StoreDecompilation::on_btnAddComplect_clicked()
{
    QJsonArray values;
    if (!C5Selector::getValue(mUser, cache_goods, values) || values.isEmpty()) {
        return;
    }
    GoodsItem g = goodsFromSelectorRow(values);
    int row = addGoods1Row(g);
    mBaseQtyOfComplectation.insert(row, 0);
}

void C5StoreDecompilation::on_leBarcode_returnPressed()
{
    QString barcode = ui->leBarcode->text().trimmed();
    ui->leBarcode->clear();
    if (barcode.isEmpty()) {
        return;
    }
    C5Cache *c = C5Cache::cache(cache_goods);
    int row = -1;
    for (int i = 0; i < c->rowCount(); i++) {
        if (c->getString(i, tr("Scancode")).compare(barcode, Qt::CaseInsensitive) == 0) {
            row = i;
            break;
        }
    }
    if (row < 0) {
        C5Message::error(tr("Wrong barcode") + "<br>" + barcode);
        return;
    }
    const int goodsId = c->getInt(row, 0);
    QString name = c->getString(row, tr("Name"));
    const QString scancode = c->getString(row, tr("Scancode"));
    if (!scancode.isEmpty()) {
        name += " | " + scancode;
    }
    ui->wcomplect->setCodeAndName(goodsId, name);
    fillComplectComposition(false);
}

int C5StoreDecompilation::addGoods1Row(GoodsItem g)
{
    int row = ui->tblGoods->addEmptyRow();
    ui->tblGoods->setInteger(row, col1_goods_id, g.id);
    ui->tblGoods->setString(row, col1_goods_name, g.name);
    ui->tblGoods->setString(row, col1_barcode, g.barcode);
    auto *leQty = ui->tblGoods->createWidget<C5LineEdit>(row, col1_qty);
    leQty->setProperty("mode", mode_qty);
    leQty->setValidator(new QDoubleValidator(0, 999999, 4));
    leQty->setFocus();
    ui->tblGoods->setString(row, col1_unit, g.unitName);
    auto *lePrice = ui->tblGoods->createLineEdit(row, col1_price);
    lePrice->setProperty("mode", mode_price);
    lePrice->setValidator(new QDoubleValidator(0, 999999999, 2));
    lePrice->setPlaceholderText(float_str(g.lastInputPrice, 2));
    auto *leTotal = ui->tblGoods->createLineEdit(row, col1_total);
    leTotal->setProperty("mode", mode_total);
    leTotal->setValidator(new QDoubleValidator(0, 999999999, 2));
    connect(leQty, &C5LineEdit::textEdited, this, &C5StoreDecompilation::rowTextEdited);
    connect(lePrice, &C5LineEdit::textEdited, this, &C5StoreDecompilation::rowTextEdited);
    connect(leTotal, &C5LineEdit::textEdited, this, &C5StoreDecompilation::rowTextEdited);
    return row;
}

void C5StoreDecompilation::on_btnFillComplect_clicked()
{
    fillComplectComposition(true);
}

void C5StoreDecompilation::fillComplectComposition(bool showEmptyError)
{
    if (ui->wcomplect->value() == 0) {
        if (showEmptyError) {
            C5Message::error(tr("Select complect"));
        }
        return;
    }
    if (ui->leQty->getDouble() < 0.0001) {
        ui->leQty->setDouble(1);
    }
    NInterface::query1("/engine/v2/officen/complect/get", mUser->mSessionKey, this,
    {{"complect_id", ui->wcomplect->value()}},
    [this, showEmptyError](const QJsonObject &jdoc) {
        QJsonArray jg = jdoc["complect_items"].toArray();
        ui->tblGoods->clearContents();
        ui->tblGoods->setRowCount(0);
        mBaseQtyOfComplectation.clear();
        if (jg.isEmpty()) {
            if (showEmptyError) {
                C5Message::error(tr("Empty document"));
            }
            return;
        }
        mComplectOut = jdoc.value("f_complectout").toDouble();
        if (mComplectOut < 0.0001) {
            mComplectOut = 1.0;
        }
        for (int i = 0; i < jg.size(); i++) {
            const QJsonObject jo = jg.at(i).toObject();
            GoodsItem gi = JsonParser<GoodsItem>::fromJson(jo);
            if (gi.barcode.isEmpty()) {
                gi.barcode = jo.value("f_scancode").toString();
            }
            int row = addGoods1Row(gi);
            const double baseQty = jo.value("f_qty").toDouble();
            mBaseQtyOfComplectation.insert(row, baseQty);
            if (gi.lastInputPrice > 0.0001) {
                ui->tblGoods->lineEdit(row, col1_price)->setDouble(gi.lastInputPrice);
            }
        }
        recountComponentQty();
    });
}

void C5StoreDecompilation::recountComponentQty()
{
    if (mComplectOut < 0.0001) {
        mComplectOut = 1.0;
    }
    const double factor = ui->leQty->getDouble() / mComplectOut;
    for (auto it = mBaseQtyOfComplectation.constBegin(); it != mBaseQtyOfComplectation.constEnd(); ++it) {
        const int row = it.key();
        if (row < 0 || row >= ui->tblGoods->rowCount()) {
            continue;
        }
        if (it.value() < 0.0000001) {
            continue;
        }
        auto *leQty = ui->tblGoods->lineEdit(row, col1_qty);
        if (!leQty) {
            continue;
        }
        const double qty = it.value() * factor;
        leQty->setDouble(qty);
        leQty->textEdited(leQty->text());
    }
}

void C5StoreDecompilation::on_leQty_textChanged(const QString &arg1)
{
    Q_UNUSED(arg1);
    recountComponentQty();
}

void C5StoreDecompilation::on_btnRemove_clicked()
{
    auto ml = ui->tblGoods->selectionModel()->selectedRows();
    if (ml.isEmpty()) {
        return;
    }
    std::sort(ml.begin(), ml.end(), [](const QModelIndex & a, const QModelIndex & b) {
        return a.row() > b.row();
    });
    for (const auto &index : ml) {
        const int row = index.row();
        ui->tblGoods->removeRow(row);
        mBaseQtyOfComplectation.remove(row);
        QMap<int, double> shifted;
        for (auto it = mBaseQtyOfComplectation.constBegin(); it != mBaseQtyOfComplectation.constEnd(); ++it) {
            shifted.insert(it.key() > row ? it.key() - 1 : it.key(), it.value());
        }
        mBaseQtyOfComplectation = shifted;
    }
}
