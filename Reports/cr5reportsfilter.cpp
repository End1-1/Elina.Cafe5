#include "cr5reportsfilter.h"
#include "ui_cr5reportsfilter.h"
#include "c5cache.h"
#include "c5lineeditwithselector.h"
#include <QDate>
#include <QSettings>

CR5ReportsFilter::CR5ReportsFilter(QWidget *parent) :
    C5FilterWidget(parent),
    ui(new Ui::CR5ReportsFilter)
{
    ui->setupUi(this);
    ui->label->show();
    ui->label_2->show();
    ui->leDate1->show();
    ui->leDate2->show();
    ui->leDate1->setDate(QDate::currentDate());
    ui->leDate2->setDate(QDate::currentDate());
    setMinimumWidth(320);
    setMinimumHeight(90);
}

CR5ReportsFilter::~CR5ReportsFilter()
{
    delete ui;
}

void CR5ReportsFilter::restoreFilter(QWidget *parent)
{
    C5FilterWidget::restoreFilter(parent);

    // Dates must always be available; base restoreFilter only applies them when fixDates is on.
    QSettings s(_ORGANIZATION_, QString("%1\\%2\\reportfilter\\%3")
                .arg(_APPLICATION_, _MODULE_, metaObject()->className()));
    const QDate d1 = s.value("date1", QDate::currentDate()).toDate();
    const QDate d2 = s.value("date2", QDate::currentDate()).toDate();
    ui->leDate1->setDate(d1.isValid() ? d1 : QDate::currentDate());
    ui->leDate2->setDate(d2.isValid() ? d2 : QDate::currentDate());
}

void CR5ReportsFilter::setFields(const QStringList &cache)
{
    int r = 0;
    for(const QString &c : cache) {
        QStringList kv = c.split("-", Qt::SkipEmptyParts);
        switch(kv.at(0).toInt()) {
        case cache_goods_partners:
            addFilterField(tr("Partner"), c, r);
            r++;
            break;
        case cache_currency:
            addFilterField(tr("Crossrate"), c, r);
            r++;
            break;
        case cache_goods_store:
            addFilterField(tr("Store"), c, r);
            r++;
            break;
        case cache_goods_group:
            addFilterField(tr("Goods group"), c, r);
            r++;
            break;
        case cache_draft_sale_state:
            addFilterField(tr("State"), c, r);
            r++;
            break;
        case cache_halls:
            addFilterField(tr("Hall"), c, r);
            break;
        case cache_partner_category:
            addFilterField(tr("Category"), c, r);
            break;
        case cache_partner_group:
            addFilterField(tr("Group"), c, r);
            break;
        case cache_partner_state:
            addFilterField(tr("State"), c, r);
            break;
        }
    }
}

QString CR5ReportsFilter::condition()
{
    return "";
}

QString CR5ReportsFilter::replacement()
{
    QString cond = " ";
    for(QMap<int, C5LineEditWithSelector*>::const_iterator it = fCache.constBegin(); it != fCache.constEnd(); it++) {
        if(!it.value()->isEmpty()) {
            switch(it.key()) {
            case cache_goods_partners:
            case cache_goods_group:
            case cache_goods_store:
            case cache_currency:
            case cache_draft_sale_state:
            case cache_halls:
                in(cond, it.value()->property("fieldname").toString(), it.value());
                break;
            }
        }
    }
    return cond;
}

QString CR5ReportsFilter::d1()
{
    return ui->leDate1->toMySQLDate();
}

QString CR5ReportsFilter::d2()
{
    return ui->leDate2->toMySQLDate();
}

void CR5ReportsFilter::addFilterField(const QString &title, const QString &data, int row)
{
    QStringList kv = data.split("-", Qt::SkipEmptyParts);
    auto *l = new QLabel(title, this);
    auto *e1 = new C5LineEditWithSelector(this);
    e1->setProperty("fieldname", kv.at(1));
    e1->setMaximumWidth(150);
    auto *e2 = new C5LineEditWithSelector(this);
    e1->setSelector(e2, kv.at(0).toInt());
    e2->setReadOnly(true);
    e2->setMinimumWidth(250);
    ui->gr->addWidget(l, row, 0);
    ui->gr->addWidget(e1, row, 1);
    ui->gr->addWidget(e2, row, 2);
    fCache[kv.at(0).toInt()] = e1;
}
