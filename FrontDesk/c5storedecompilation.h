#pragma once

#include "c5widget.h"
#include "struct_goods_item.h"
#include <QMap>

namespace Ui
{
class C5StoreDecompilation;
}

class C5StoreDecompilation : public C5Widget
{
    Q_OBJECT
public:
    explicit C5StoreDecompilation(QWidget *parent = nullptr);

    ~C5StoreDecompilation();

    virtual QToolBar* toolBar() override;

private slots:
    void rowTextEdited(const QString &text);

    void saveDoc();

    void draftDoc();

    void removeDocument();

    void printDoc();

    void on_btnAddComplect_clicked();

    void on_leBarcode_returnPressed();

    void on_btnFillComplect_clicked();

    void on_btnRemove_clicked();

    void on_leQty_textChanged(const QString &arg1);

private:
    Ui::C5StoreDecompilation* ui;

    QString mInternalId;

    int mDocState = 0;

    double mComplectOut = 1.0;

    QMap<int, double> mBaseQtyOfComplectation;

    int addGoods1Row(GoodsItem g);

    void fillComplectComposition(bool showEmptyError);

    void recountComponentQty();

    bool writeDocument(int state);

    void setDocEnabled(bool enabled);
};
