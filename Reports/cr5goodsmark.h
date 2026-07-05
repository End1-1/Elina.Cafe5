#ifndef CR5GOODSMARK_H
#define CR5GOODSMARK_H

#include "c5reportwidget.h"

class CR5GoodsMark : public C5ReportWidget
{
    Q_OBJECT

public:
    CR5GoodsMark(QWidget *parent = nullptr);

    virtual QToolBar *toolBar();
};

#endif // CR5GOODSMARK_H
