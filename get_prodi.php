<?php
require_once 'functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['fakultas_id'])) {
    $fakultas_id = (int)$_POST['fakultas_id'];
    $prodi = getProdiByFakultas($fakultas_id);

    $options = '';
    foreach ($prodi as $p) {
        $options .= "<option value='{$p['id']}'>{$p['nama_prodi']}</option>";
    }

    echo $options;
}
?>
