import { Controller } from '@hotwired/stimulus';

export default class extends Controller {

    grid
    dataTable
    static targets = []
    static values = {
        loadUrl: String,
        columns: Array
    }

    connect() {
        // this.initDataTable()
    }

    initDataTable(){
        this.dataTable = new DataTable({
            dom: '<"top">rt<"bottom-tbar"<"row p-2"<"col-lg-4"l><"col-lg-8"p>>>',
            paging: true,
            lengthChange: true,
            lengthMenu: [
                [10, 25, 50, 100, 250],
                [10, 25, 50, 100, 250],
            ],
            searching: true,
            info: true,
            autoWidth: false,
            ajax: {
                url: _this.$table.data('source'),
                method: 'GET',
                data: function (d) {
                    let filterData = _this.$formFilter.serializeArray();
                    filterData.push({name: 'length', value: d.length});
                    filterData.push({name: 'start', value: d.start});
                    if (d.order.length > 0) {
                        filterData.push({name: 'order_column', value: d.order[0].column});
                        filterData.push({name: 'order_dir', value: d.order[0].dir});
                    } else {

                        filterData.push({name: 'order_column', value: 0});
                        filterData.push({name: 'order_dir', value: 'asc'});
                    }
                    let filter = _this.encodeFilterForm();
                    const event = new CustomEvent('api_dt_preload', {
                        detail: {
                            url: _this.$table.data('source'),
                            filterData: filter
                        }
                    });
                    _this.$table[0].dispatchEvent(event);
                    return filterData;
                },
                dataSrc: function (response) {
                    // loadUrls = [];
                    // response.data.forEach(function (r) {
                    //     loadUrls.push(r.pop());
                    // });
                    return response.data;
                }
            },
            ordering: _this.$table.data('ordering-enabled'),
            select: selectionScheme,
            order: [[0, 'asc']],
            processing: true,
            serverSide: true,
            drawCallback: function (oSettings) {
                _this.$table.find('[data-toggle="tooltip"]').tooltip();
            }
        });
    }


}
