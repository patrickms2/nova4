import { employeesTeamSales } from '../common/employee-and-team-sales';
import { employeeAverageSales } from '../common/employee-average-sales';
import { employeeListSales } from '../common/employee-list-sales';
import { employeeList } from '../common/employee-list';
import { employeeQuarterSales } from '../common/employee-quarter-sales';
import { employeeSales } from '../common/employees-sales';


import '@progress/kendo-ui/kendo.grid.js';
import '@progress/kendo-ui/kendo.listview.js';
import '@progress/kendo-ui/kendo.dataviz.chart.js';
import '@progress/kendo-ui/kendo.scheduler.js';

(() => {
    var employeeAndTeamSales = employeesTeamSales;
    var employeeSchedulerSales = employeeSales;
    var employeeListSales = employeeListSales;

    $("#currentYear").html(new Date().getFullYear());
    $("#employees-list").kendoListView({
        template: $('#employeeItemTemplate').html(),
            dataSource: {
                data: employeeList,
                pageSize: 9
            },
            selectable: "single",
            dataBound: onListDataBound,
            change: onCriteriaChange
    });

    $("#start-date").kendoDatePicker({
        value: new Date(1996, 6, 1),
        change: onCriteriaChange
    })

    $("#end-date").kendoDatePicker({
        value: new Date(1998, 7, 1),
        change: onCriteriaChange
    })

    $("#employee-quarter-sales").kendoChart({
        theme: "metro",
        autoBind: false,
        tooltip: false,
        dataBound: onQuarterSalesDataBound,
        dataSource: {
            transport: {
                read: function (options) {
                    var result = $.grep(employeeQuarterSales, function (e) {
                        return e.EmployeeID == options.data.EmployeeID;
                    })[0];

                    options.success(result.Sales)
                }
            }
        },
        series: [{
            type: "bullet",
            currentField: "Current",
            targetField: "Target"

        }],
        legend: {
            visible: false
        },
        categoryAxis: {
            labels: {
                visible: false
            },
            majorGridLines: {
                visible: false
            }
        },
        valueAxis: {
            type: "numeric",
            labels: {
                visible: false
            },
            majorTicks: {
                visible: false
            },
            majorGridLines: {
                visible: false
            }
        }
    })

    $("#employee-average-sales").kendoChart({
        theme: "metro",
        autoBind: false,
        dataBound: onAverageSalesDataBound,
        dataSource: {
            transport: {
                read: function (options) {
                    var result = $.grep(employeeAverageSales, function (e) {
                        return e.EmployeeID == options.data.EmployeeID;
                    });
                    options.success(result);
                }
            },
            aggregate: [{
                field: "EmployeeSales",
                aggregate: "average"
            }]
        },
        series: [{
            type: "line",
            field: "EmployeeSales",
            width: 1.5,
            markers: {
                visible: false
            }
        }],
        categoryAxis: {
            type: "date",
            field: "Date",
            visible: false,
            majorGridLines: {
                visible: false
            },
            majorTicks: {
                visible: false
            }
        },
        legend: {
            visible: false
        },
        valueAxis: {
            type: "numeric",
            visible: false,
            labels: {
                visible: false
            },
            majorGridLines: {
                visible: false
            },
            majorTicks: {
                visible: false
            }
        }
    });

    $("#team-sales").kendoChart({
        theme: "metro",
        title: {
            text: "REPRESENTATIVE SALES VS. TOTAL SALES",
            align: "left",
            font: "11px",
            color: "#35373d"
        },
        autoBind: false,
        dataSource: {
            transport: {
                read: function (options) {

                    var startDate = $("#start-date").data("kendoDatePicker").value();
                    var endDate = $("#end-date").data("kendoDatePicker").value()
                    var employee = $.grep(employeeAndTeamSales, function (e) {
                        return e.EmployeeID == options.data.EmployeeID;
                    })[0];

                    var filtered = employee.Sales.filter(x => new Date(x.Date) >= startDate && new Date(x.Date) <= endDate)

                    options.success(filtered);
                }
            }
        },
        legend: {
            position: "bottom"
        },
        series: [{
            field: "EmployeeSales",
            categoryField: "Date",
            name: "Employee Sales",
            aggregate: "sum"
        }, {
            field: "TotalSales",
            categoryField: "Date",
            name: "Team Sales",
            aggregate: "sum"
        }],
        categoryAxis: {
            type: "date",
            baseUnit: "months",
            majorGridLines: {
                visible: false
            }
        },
        valueAxis: {
            labels: {
                format: "{0:c2}",
                visible: false
            },
            majorUnit: 25000,
            line: {
                visible: false
            },
            majorGridLines: {
                visible: false
            }
        },
        tooltip: {
            visible: true,
            format: "{0:c2}"
        }
    })

    $("#employee-sales").kendoScheduler({
        autoBind: false,
        date: new Date(1996, 1,7),
        views: ["month"],
        editable: false,
        timezone: "Etc/UTC",
        dataSource: {
            transport: {
                read: function (options) {
                    options.success(employeeSchedulerSales);
                }
            },
            schema: {
                model: {
                    fields: {
                        SaleID: {
                            type: "number"
                        },
                        title: {
                            from: "Title",
                            type: "string"
                        },
                        description: {
                            from: "Description",
                            type: "string"
                        },
                        start: {
                            from: "Start",
                            type: "date"
                        },
                        startTimezone: {
                            from: "StartTimezone",
                            type: "string"
                        },
                        end: {
                            from: "End",
                            type: "date"
                        },
                        endTimezone: {
                            from: "EndTimezone",
                            type: "string"
                        },
                        recurrenceRule: {
                            from: "RecurrenceRule",
                            type: "string"
                        },
                        RecurrenceID: {
                            type: "number",
                            defaultValue: null
                        },
                        recurrenceException: {
                            from: "RecurrenceException",
                            type: "string"
                        },
                        isAllDay: {
                            from: "IsAllDay",
                            type: "boolean"
                        },
                        EmployeeID: {
                            type: "number",
                            defaultValue: null
                        }
                    }
                }
            }
        }
    })



    $('#employeeBio').kendoTooltip({
        filter: "a",
        content: function (e) {
            return e.target.find("span").text();
        }
    })

    function onListDataBound(e) {
        this.select($(".employee:first"));
        setTimeout(function(){
            e.sender.trigger('change')
            kendo.ui.icon($(".icon-mobile"), { icon: 'user' });
        })

    }

    function onCriteriaChange() {
        var employeeList = $("#employees-list").data("kendoListView"),
            employee = employeeList.dataSource.getByUid(employeeList.select().attr("data-uid")),
            employeeQuarterSales = $("#employee-quarter-sales").data("kendoChart"),
            employeeAverageSales = $("#employee-average-sales").data("kendoChart"),
            teamSales = $("#team-sales").data("kendoChart"),
            employeeSales = $("#employee-sales").data("kendoScheduler"),
            startDate = $("#start-date").data("kendoDatePicker"),
            endDate = $("#end-date").data("kendoDatePicker"),
            filter = {
                EmployeeID: employee.EmployeeID,
                startDate: new Date(startDate.value()),
                endDate: new Date(endDate.value())
            },
            template = kendo.template($("#employeeBioTemplate").html());

            if(endDate.value() < startDate.value()){
                alert('Invalid date range')
            }


        $("#employeeBio").html(template(employee));

        employeeSales.dataSource.filter({
            field: "EmployeeID",
            operator: "eq",
            value: employee.EmployeeID
        });

        employeeSales.date(startDate.value());

        teamSales.dataSource.read(filter);
        employeeQuarterSales.dataSource.read(filter);
        employeeQuarterSales.resize();
        employeeAverageSales.dataSource.read(filter);
    }

    function onQuarterSalesDataBound(e) {
        var data = this.dataSource.at(0);
        $("#employee-quarter-sales-label").text(kendo.toString(data.Current, "c2"));
    }

    function onAverageSalesDataBound(e) {
        var data = this.dataSource.aggregates()
        if (data.EmployeeSales) {
            $("#employee-average-sales-label").text(kendo.toString(data.EmployeeSales.average, "c2"));
        } else {
            $("#employee-average-sales-label").text(kendo.toString(0, "c2"));
        }
    }
})();