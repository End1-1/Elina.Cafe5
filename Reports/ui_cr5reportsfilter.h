/********************************************************************************
** Form generated from reading UI file 'cr5reportsfilter.ui'
**
** Created by: Qt User Interface Compiler version 6.10.2
**
** WARNING! All changes made in this file will be lost when recompiling UI file!
********************************************************************************/

#ifndef UI_CR5REPORTSFILTER_H
#define UI_CR5REPORTSFILTER_H

#include <QtCore/QVariant>
#include <QtWidgets/QApplication>
#include <QtWidgets/QGridLayout>
#include <QtWidgets/QLabel>
#include <QtWidgets/QSpacerItem>
#include <QtWidgets/QWidget>
#include <c5dateedit.h>

QT_BEGIN_NAMESPACE

class Ui_CR5ReportsFilter
{
public:
    QGridLayout *gridLayout;
    QLabel *label;
    C5DateEdit *leDate1;
    QLabel *label_2;
    C5DateEdit *leDate2;
    QGridLayout *gr;
    QSpacerItem *verticalSpacer;

    void setupUi(QWidget *CR5ReportsFilter)
    {
        if (CR5ReportsFilter->objectName().isEmpty())
            CR5ReportsFilter->setObjectName("CR5ReportsFilter");
        CR5ReportsFilter->resize(402, 300);
        gridLayout = new QGridLayout(CR5ReportsFilter);
        gridLayout->setObjectName("gridLayout");
        label = new QLabel(CR5ReportsFilter);
        label->setObjectName("label");

        gridLayout->addWidget(label, 0, 0, 1, 1);

        leDate1 = new C5DateEdit(CR5ReportsFilter);
        leDate1->setObjectName("leDate1");
        QSizePolicy sizePolicy(QSizePolicy::Policy::Expanding, QSizePolicy::Policy::Fixed);
        sizePolicy.setHorizontalStretch(1);
        sizePolicy.setVerticalStretch(0);
        sizePolicy.setHeightForWidth(leDate1->sizePolicy().hasHeightForWidth());
        leDate1->setSizePolicy(sizePolicy);

        gridLayout->addWidget(leDate1, 0, 1, 1, 1);

        label_2 = new QLabel(CR5ReportsFilter);
        label_2->setObjectName("label_2");

        gridLayout->addWidget(label_2, 1, 0, 1, 1);

        leDate2 = new C5DateEdit(CR5ReportsFilter);
        leDate2->setObjectName("leDate2");
        sizePolicy.setHeightForWidth(leDate2->sizePolicy().hasHeightForWidth());
        leDate2->setSizePolicy(sizePolicy);

        gridLayout->addWidget(leDate2, 1, 1, 1, 1);

        gr = new QGridLayout();
        gr->setObjectName("gr");

        gridLayout->addLayout(gr, 2, 0, 1, 2);

        verticalSpacer = new QSpacerItem(20, 40, QSizePolicy::Policy::Minimum, QSizePolicy::Policy::Expanding);

        gridLayout->addItem(verticalSpacer, 3, 0, 1, 2);


        retranslateUi(CR5ReportsFilter);

        QMetaObject::connectSlotsByName(CR5ReportsFilter);
    } // setupUi

    void retranslateUi(QWidget *CR5ReportsFilter)
    {
        CR5ReportsFilter->setWindowTitle(QCoreApplication::translate("CR5ReportsFilter", "Form", nullptr));
        label->setText(QCoreApplication::translate("CR5ReportsFilter", "Date start", nullptr));
        leDate1->setProperty("FilterName", QVariant(QCoreApplication::translate("CR5ReportsFilter", "date1", nullptr)));
        label_2->setText(QCoreApplication::translate("CR5ReportsFilter", "Date end", nullptr));
        leDate2->setProperty("FilterName", QVariant(QCoreApplication::translate("CR5ReportsFilter", "date2", nullptr)));
    } // retranslateUi

};

namespace Ui {
    class CR5ReportsFilter: public Ui_CR5ReportsFilter {};
} // namespace Ui

QT_END_NAMESPACE

#endif // UI_CR5REPORTSFILTER_H
