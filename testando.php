<?php

include "connect.php";

use League\Csv\Reader;

require '../vendor/autoload.php';

if(isset($_FILES['excel']) && $_FILES['excel']['error'] === UPLOAD_ERR_OK) {
    $excel = $_FILES['excel']['name'];
    $nomeArquivo = pathinfo($excel, PATHINFO_FILENAME);

    $diretorioDestino = './Arquivos/';

    // Move o arquivo para o diretório de destino
    move_uploaded_file($_FILES['excel']['tmp_name'], $diretorioDestino . $excel);

    $sql = "CREATE TABLE IF NOT EXISTS bancos (nome VARCHAR(50), data VARCHAR(50))";
    $conn->query($sql);
    

    $csv = Reader::createFromPath("$diretorioDestino$nomeArquivo".'.CSV', 'r');
    $csv->setHeaderOffset(1);

    $firstRow = $csv->fetchOne();

    $valor = implode(';', $firstRow);

    $valor1 = explode(';', $valor);

    $sql = "CREATE TABLE IF NOT EXISTS $nomeArquivo (";

    $contador = 0;

    foreach ($valor1 as $valor0) {
        if (!empty($valor0)) {
        $contador++;
            $sql .= "$valor0 varchar(100), ";
        }
    }

    $sql = rtrim($sql, ", ");

    $sql .= ")";

    if ($conn->query($sql)) {
        $nomeArquivoLower = strtolower($nomeArquivo);
        $data = date("H-i-s d/m/Y");
        
        $sqlInsert = "INSERT INTO bancos (nome, data) VALUES (?, ?)";
        $stmt = $conn->prepare($sqlInsert);
        
        if ($stmt) {
            $stmt->bind_param('ss', $nomeArquivoLower, $data);
            $stmt->execute();
        } else {
            echo "Erro na preparação da declaração SQL.";
        }
    }

    $sql = '';

    $sql = "INSERT INTO $nomeArquivo VALUES(";

    $csv = Reader::createFromPath("$diretorioDestino$nomeArquivo".'.CSV', 'r')->setDelimiter(';');

    $csv->setHeaderOffset(0); 
    $csv->fetchOne();


    foreach ($csv as $valores) {
        $contagem = 0;

        foreach ($valores as $resultados) {
            $contagem++;
            $resultados = str_replace(",", ".", $resultados);
            $resultados = str_replace("'", "", $resultados);
            $resultados = str_replace('"', "", $resultados);
            $sql .= $resultados;

            if ($contagem == 1) {
                $sql .= ",'";
            }

            if ($contagem > 1 && $contagem < $contador) {
                $sql .= "','";
            }

            if ($contagem == $contador) {
                $sql .= "'),(";
            }
        }
    }

    $sql = rtrim($sql, ',(');

    if ($conn->query($sql)) {
        $caminhoCompleto = "$diretorioDestino$nomeArquivo".'.CSV';
        unlink($caminhoCompleto);

        header("location: ./concluiu.php");
        exit();
    } else {
        echo "Erro ao inserir dados: " . $conn->error;
    }
} else {
    echo "Erro ao enviar o arquivo.";
}