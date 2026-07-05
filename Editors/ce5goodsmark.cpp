#include "ce5goodsmark.h"
#include "ui_ce5goodsmark.h"

CE5GoodsMark::CE5GoodsMark(QWidget *parent) :
    CE5Editor(parent),
    ui(new Ui::CE5GoodsMark)
{
    ui->setupUi(this);
}

CE5GoodsMark::~CE5GoodsMark()
{
    delete ui;
}

QString CE5GoodsMark::title()
{
    return tr("Goods mark");
}

QString CE5GoodsMark::table()
{
    return "c_goods_mark";
}
