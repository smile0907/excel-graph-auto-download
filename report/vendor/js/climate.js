let start, end, estacion;

const climateFilter = () => {
    const start_date = $("#start-date").val();
    const end_date = $("#end-date").val();

    estacion = $("#estacion-filter").val();
    start = start_date;
    end = end_date;

    reportClimateTable.destroy();
    $("#report-climate-loading").loading('circle1');
    $.post("vendor/server/climate.php", { start, end, estacion, type: "climate-filter" }).then((result) => {
        result = JSON.parse(result);
        const { climate_reports } = result;
        climateData = climate_reports;
        renderClimateTable(climate_reports);
        $("#report-climate-loading").loading(false);
    })
}

$(document).ready(() => {
    $("#customFileClimate").on("change", function() {
        var fileName = $(this).val().split("\\").pop();
        $(this).siblings("#customFileClimateLabel").addClass("selected").html(fileName);
        const file = $(this)[0].files[0];
        const { type } = file;
        if (type==='application/vnd.ms-excel') {
            reportClimateTable.destroy();
            $("#report-climate-loading").loading('circle1');
            var fd = new FormData();
            var file_name = `${new Date().getTime()}Climate.xls`;

            fd.append("climate", file);
            fd.append("file_name", file_name);
            fd.append("type", "upload_climate_file");
            $.ajax({
                url: 'vendor/server/climate.php',
                type: 'POST',
                data: fd,
                success: function () {
                    $.post("vendor/server/climateExcel.php", { fileName: file_name }).then(() => {
                        $.toast({
                            heading: 'Success',
                            text: 'Climate Data is saved succesfully!',
                            position: 'top-right',
                            stack: false,
                            icon: 'success'
                        })
                        $.post("vendor/server/getReport.php", { type: "get_climate" }).then((result) => {
                            result = JSON.parse(result);
                            const { climate_reports, product_reports } = result;
                            climateData = climate_reports;
                            productData = product_reports;
                            renderClimateTable(climate_reports);
                            $("#report-climate-loading").loading(false);
                        })
                    })
                },
                cache: false,
                contentType: false,
                processData: false
            });
        } else $.toast({
            heading: 'Warning',
            text: 'Please select the xls file!',
            position: 'top-right',
            stack: false,
            icon: 'warning'
        });
    });
})
