#ifndef CE5GOODSMARK_H
#define CE5GOODSMARK_H

#include "ce5editor.h"

namespace Ui {
class CE5GoodsMark;
}

class CE5GoodsMark : public CE5Editor
{
    Q_OBJECT

public:
    explicit CE5GoodsMark(QWidget *parent = nullptr);

    ~CE5GoodsMark();

    virtual QString title();

    virtual QString table();

private:
    Ui::CE5GoodsMark *ui;
};

#endif // CE5GOODSMARK_H
