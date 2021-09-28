<?php
    include "C:/xampp/php/Classes/PHPExcel/IOFactory.php";
    require_once "../../../connect.php";
    require_once "../../../vendor/server/monthArray.php";
    $fileName = $_POST["fileName"];
    $inputFileName = "../../../files/".$fileName;

    //  Read your Excel workbook
    try {
        $inputFileType = PHPExcel_IOFactory::identify($inputFileName);
        $objReader = PHPExcel_IOFactory::createReader($inputFileType);
        $objPHPExcel = $objReader->load($inputFileName);
    } catch(Exception $e) {
        die('Error loading file "'.pathinfo($inputFileName,PATHINFO_BASENAME).'": '.$e->getMessage());
    }

    //  Get worksheet dimensions
    $sheet = $objPHPExcel->getSheet(0); 
    $highestRow = $sheet->getHighestRow(); 
    $highestColumn = $sheet->getHighestColumn();
    
    //  Loop through each row of the worksheet in turn
    for ($row = 2; $row <= $highestRow; $row++) {
        //  Read a row of data into an array
        $rowData = $sheet->rangeToArray('A' . $row . ':' . $highestColumn . $row,
                                        NULL,
                                        TRUE,
                                        FALSE);
        $rowData = $rowData[0];

        $Estacion = $rowData[1];
        $Fecha = $rowData[2];
        $temperatura = $rowData[3];
        $temperatura_minima = $rowData[4];
        $temperatura_maxima = $rowData[5];
        $radiacion = $rowData[6];
        $radiacion_promedio = $rowData[7];
        $humedad_relativa = $rowData[8];
        $humedad_relativa_minima = $rowData[9];
        $humedad_relativa_maxima = $rowData[10];
        $precipitacion = $rowData[11];
        $velocidad_viento = $rowData[12];
        $velocidad_viento_minima = $rowData[13];
        $velocidad_viento_maxima = $rowData[14];
        $mojadura = $rowData[15];
        $presion_atmosferica = $rowData[16];
        $presion_atmosferica_minima = $rowData[17];
        $presion_atmosferica_maxima = $rowData[18];
        $direccion_viento = $rowData[19];
        $FechaType = gettype($Fecha);
        if ($FechaType==='string') {
            $Fecha = date_create_from_format('d/m/Y', $Fecha);
            $Fecha = $Fecha->format('d/m/Y');
        } else {
            $Fecha = date('d/m/Y', PHPExcel_Shared_Date::ExcelToPHP($Fecha));
        }

        $sql = "SELECT Estacion, Fecha FROM excel WHERE Estacion='$Estacion' AND Fecha='$Fecha'";
        $result = $conn->query($sql);
        if ($result->num_rows==0) {
            $insert_query = "INSERT INTO excel (Estacion, Fecha, temperatura, temperatura_minima,
                temperatura_maxima, radiacion, radiacion_promedio, humedad_relativa, humedad_relativa_minima,
                humedad_relativa_maxima, precipitacion, velocidad_viento, velocidad_viento_minima,
                velocidad_viento_maxima, mojadura, presion_atmosferica, presion_atmosferica_minima,
                presion_atmosferica_maxima, direccion_viento)
            VALUES ('$Estacion', '$Fecha', '$temperatura', '$temperatura_minima',
                '$temperatura_maxima', '$radiacion', '$radiacion_promedio', '$humedad_relativa', '$humedad_relativa_minima',
                '$humedad_relativa_maxima', '$precipitacion', '$velocidad_viento', '$velocidad_viento_minima',
                '$velocidad_viento_maxima', '$mojadura', '$presion_atmosferica', '$presion_atmosferica_minima',
                '$presion_atmosferica_maxima', '$direccion_viento')";
            $confirm = $conn->query($insert_query);
        }

        $FechaData = date_create_from_format('d/m/Y', $Fecha);
        $yearStr = $FechaData->format('Y');
        $monthStr = $FechaData->format('m');
        $dateStr = $FechaData->format('d');
        
        $year = (int)$yearStr;
        $month = (int)$monthStr;
        $date = (int)$dateStr;
        $dateStr = '0' . $date;
        $mon = $monthArray[$month];

        $Fecha = $FechaData->format('Y-m-d');

        $sql_zafra = "SELECT zafra FROM zafra_masters WHERE ano='$year' AND mes='$month'";
        $result_zafra = $conn->query($sql_zafra);
        $zafra = "";

        foreach($result_zafra as $row_zafra) {
            $zafra = $row_zafra["zafra"];
        }

        $ID_COMP = "";
        $radiacion = $radiacion * 0.00089681;
        $tem = $temperatura;
        $temMin = $temperatura_minima;
        $temMax = $temperatura_maxima;
        $hum = $humedad_relativa;
        $humMin = $humedad_relativa_minima;
        $humMax = $humedad_relativa_maxima;
        $velMin = $velocidad_viento_minima;
        $velMax = $velocidad_viento_maxima;
        $dir = $direccion_viento;
        $Codigo_buscar = $yearStr.$monthStr.$dateStr;
        $ETP = 0.1;
        $R0 = 0.1;
        $Rso = 0.1;
        $pendiente_curva = 0.1;
        $presion_de = 0.1;
        $presion_real_de = 0.1;
        $Cuadrante = "";
        $Estrato = "";
        $Altitude_Use = 0.1;
        $Region = "";
        $Kpa = 0.1;
        $Eto_PENMAN = 0.1;
        $Aplica = "";

        $sql_ro = "SELECT R_0 FROM master_ro WHERE Mes='$mon'";
        $result_master_ro = $conn->query($sql_ro);
        
        foreach($result_master_ro as $row_master_ro) {
            $R0 = $row_master_ro["R_0"];
        }

        $sql_ra = "SELECT Rso FROM master_ra WHERE ANO='$year' AND MES='$month' AND DIA='$dateStr'";
        $result_master_ra = $conn->query($sql_ra);
        
        foreach($result_master_ra as $row_master_ra) {
            $Rso = $row_master_ra["Rso"];
        }

        $sql_batches = "SELECT * FROM master_batches WHERE NOMBRE='$Estacion'";
        $result_master_batches = $conn->query($sql_batches);
        
        foreach($result_master_batches as $row_master_batches) {
            $ID_COMP .= (($ID_COMP!=="") ? (','. $row_master_batches["ID_COMP"]) : $row_master_batches["ID_COMP"]);
            $Cuadrante = $row_master_batches["Cuadrante"];
            $Estrato = $row_master_batches["Estrato"];
            $Altitude_Use = $row_master_batches["ALTITUD_Utilizar"];
            $Region = $row_master_batches["Region"];
            $Aplica = $row_master_batches["Aplica"];
        }

        if ($radiacion > 30) $radiacion = 30;
        if ($temMax > 42) $temMax = 42;
        if ($temMax < 20) $temMax = 20;
        if ($temMin < 12) $temMin = 12;
        if ($temMin > 25) $temMin = 25;
        if ($hum > 100) $hum = 100;
        if ($hum < 15) $hum = 15;
        if ($humMax > 100) $humMax = 100;
        if ($humMax < 75) $humMax = 75;
        if ($humMin > 75) $humMin = 75;
        if ($humMin < 15) $humMin = 15;
        if ($velMin < 0) $velMin = 0;
        if ($velMin > 5) $velMin = 5;
        if ($velMax > 60) $velMax = 60;
        if ($velMax < 5) $velMax = 5;
        if ($dir < 0) $dir = 0;
        if ($dir > 360) $dir = 360;

        $Rns = $radiacion * 0.77;
        $Amplitud_Termica = $temMax - $temMin;

        $ETP = 0.0135 * ($tem + 17.78) * 0.19 * $R0 * pow(($temMax - $temMin), 0.5);
        $pendiente_curva = 4098 * ((0.6108 * (exp((17.27 * $tem) / ($tem + 237.3)))) / pow(($tem + 237.3), 2));
        $presion_de = ((0.6108*exp((17.27*$temMax)/($temMax+237.3)))+(0.6108*exp((17.27*$temMin)/($temMin+237.3)))) / 2;
        $presion_real_de = ((0.6108*EXP((17.27*$temMax)/($temMax+237.3))) * ($humMin/100)+(0.6108*exp((17.27*$temMin)/($temMin+237.3)))*( $humMax / 100))/2;
        $deficit_presion = $presion_de - $presion_real_de;
        $Rnl = (((0.000000004903*(pow(($temMax+273.16), 4)))+((0.000000004903*(pow(($temMin+273.16), 4)))))/2)*(0.34- (0.14*sqrt($presion_real_de)))*(1.35*($radiacion/((0.75+2*($Altitude_Use/100000))*$Rso))-0.35);
        $Eto_Hargreaves = 0.0023*($temMin+17.78)*( $Rso * 0.408)*(sqrt($temMax-$temMin));
        $velocidad_estandar = ((4.87)/((log((67.8*10)-5.42))))*(($velocidad_viento)/3600);
        $Kpa = ((pow(((293 - (0.0065 * $Altitude_Use))/293), 5.26)) * 101.3) * 0.000665;
        $Rn = $Rns - $Rnl;
        $Eto_PENMAN = ($pendiente_curva/(($pendiente_curva+($Kpa*(1+(0.34*($velocidad_viento*0.20794)))))))*(0.408*$Rn)+(($Kpa/(($pendiente_curva+($Kpa*(1+(0.34*($velocidad_viento*0.20794)))))))*(900/($tem+273))*($velocidad_viento*0.20794)*$deficit_presion);

        $sql_clima = "SELECT Fecha FROM reporte_clima WHERE Fecha='$Fecha' AND Estacion='$Estacion'";
        $clima = $conn->query($sql_clima);
        if ($clima->num_rows==0) {
            $clima_insert_query = "INSERT INTO reporte_clima (
                Estacion, Fecha, temperatura, temperatura_min, temperatura_max, Radiacion, ID_COMP,
                Ano, Mes, Dia, R0, Zafra, Amplitud_Termica, radiacion_promedio, humedad_relativa,
                humedad_relativa_minima, humedad_relativa_maxima, precipitacion, velocidad_viento,
                velocidad_viento_minima, velocidad_viento_maxima, mojadura, presion_atmosferica,
                presion_atmosferica_minima, presion_atmosferica_maxima, direccion_viento, Rso, Rns, ETP,
                pendiente_curva, presion_de, presion_real_de, deficit_presion, Eto_Hargreaves, velocidad_estandar,
                Cuadrante, Estrato, Altitude_Use, Region, Codigo_buscar, Kpa, Rnl, Rn, Eto_PENMAN, Aplica)
            VALUES (
                '$Estacion', '$Fecha', '$tem', '$temMin', '$temMax', '$radiacion', '$ID_COMP',
                '$yearStr', '$monthStr', '$dateStr', '$R0', '$zafra', '$Amplitud_Termica', '$radiacion_promedio', '$hum',
                '$humMin', '$humMax', '$precipitacion', '$velocidad_viento',
                '$velMin', '$velMax', '$mojadura', '$presion_atmosferica',
                '$presion_atmosferica_minima', '$presion_atmosferica_maxima', '$dir', '$Rso', '$Rns', '$ETP',
                '$pendiente_curva', '$presion_de', '$presion_real_de', '$deficit_presion', '$Eto_Hargreaves', '$velocidad_estandar',
                '$Cuadrante', '$Estrato', '$Altitude_Use', '$Region', '$Codigo_buscar', '$Kpa', '$Rnl', '$Rn', '$Eto_PENMAN', '$Aplica')";
            $confirm_clima = $conn->query($clima_insert_query);
        }
    }
    echo json_encode(array("success"=>true));
?>
