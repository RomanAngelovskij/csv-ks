<?php
$csvDir = $_SERVER['DOCUMENT_ROOT'] . 'tmp_csv/';
$voluumFile = $csvDir . 'voluum.csv';
$exoFile = $csvDir . 'exo.csv';
$completedData = [];
$errors = [];

if (!empty($_FILES['exo']['tmp_name']) && !empty($_FILES['voluum']['tmp_name'])) {
    if (!file_exists($csvDir)) {
        mkdir($csvDir);
        chmod($csvDir, 0777);
    }

    if (move_uploaded_file($_FILES['exo']['tmp_name'], $exoFile) === false) {
        $errors[] = 'Не удалось загрузить файл EXO';
    }

    if (move_uploaded_file($_FILES['voluum']['tmp_name'], $voluumFile) === false) {
        $errors[] = 'Не удалось загрузить файл VOLUUM';
    }
}

if (!empty($_POST) || isset($_GET['order'])) {
    if (file_exists($exoFile) === false) {
        $errors[] = 'Отсутствует фаил '  . $exoFile;
    }

    if (file_exists($voluumFile) === false) {
        $errors[] = 'Отсутствует фаил '  . $voluumFile;
    }

    if (empty($errors)) {

        $minTraffic = !empty($_POST['minTraffic']) ? $_POST['minTraffic'] : 0;
        $minPrice = !empty($_POST['minPrice']) ? $_POST['minPrice'] : 0;

        $exoRows = explode("\n", file_get_contents($exoFile));
        $exoTitles = explode(',', array_shift($exoRows));

        $exoData = [];
        foreach ($exoRows as $row) {
            $data = explode(',', $row);
            if ($data[3] >= $minTraffic && $data[6] >= $minPrice) {
                $exoData[$data[0]] = [];
                foreach ($data as $index => $value) {
                    $exoData[$data[0]][$exoTitles[$index]] = $value;
                }
            }
        }

        $voluumRows = explode("\n", file_get_contents($voluumFile));
        $voluumTitles = explode(',', array_shift($voluumRows));

        foreach ($voluumRows as $row) {
            $data = explode(',', $row);
            $siteName = str_replace('"', '', $data[0]);

            if (isset($exoData[$siteName])) {
                $completedData[] = [
                    'site' => $siteName,
                    'impressions' => $exoData[$siteName]['impressions'],
                    'conversions' => $data[2],
                    'cost' => $exoData[$siteName]['cost'],
                    'revenue' => $data[3],
                    'cv' => $data[2] / $exoData[$siteName]['impressions'] * 100,
                    'roi' => ($data[3]-$exoData[$siteName]['cost'])/$exoData[$siteName]['cost']*100,
                ];
            }
        }
    }

    if (empty($errors) && isset($_GET['order'])){
        $sortArray = [];

        foreach($completedData as $row){
            foreach($row as $key => $value){
                if(!isset($sortArray[$key])){
                    $sortArray[$key] = [];
                }
                $sortArray[$key][] = $value;
            }
        }

        $orderby = trim($_GET['order']);
        $sortOrders = ['asc' => SORT_ASC, 'desc' => SORT_DESC];

        array_multisort($sortArray[$orderby], $sortOrders[$_GET['sortOrder']], $completedData);

        unset($sortArray);
    }
}

function var_dump_pre($val)
{
    echo '<pre>';
    var_dump($val);
    echo '</pre>';
}

?>

<html>
<head>
    <title>CSV</title>
    <meta charset="utf-8">

    <link rel="stylesheet" href="css/bootstrap.min.css">
</head>
<body>
<div class="container">
    <?php if (!empty($errors)):?>
        <div class="alert alert-danger">
        <?php foreach ($errors as $error):?>
            <li><?=$error?>
        <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form action="" method="post" enctype="multipart/form-data">
        <div class="form-group">
            <label for="csv_1">EXO:</label>
            <input type="file" name="exo" id="csv_1" class="form-control">
        </div>

        <div class="form-group">
            <label for="csv_2">VOLUUM:</label>
            <input type="file" name="voluum" id="csv_2" class="form-control">
        </div>

        <div class="form-group">
            <label for="minTraffic">Минимальное количество трафика</label>
            <input type="text" name="minTraffic" id="minTraffic" class="form-control">
        </div>

        <div class="form-group">
            <label for="minPrice">Минимальная цена для учёта трафика</label>
            <input type="text" name="minPrice" id="minPrice" class="form-control">
        </div>

        <input type="submit" value="Обработать файлы" class="btn btn-success">
    </form>

    <?php if (isset($completedData)): ?>
        <?php if (!empty($completedData)): ?>
            <table class="table table-bordered">
                <thead>
                <tr>
                    <td><a href="?order=site&sortOrder=<?= (isset($_GET['order']) && $_GET['order'] == 'site' && $_GET['sortOrder'] == 'desc') ? 'asc' : 'desc' ?>">Домен</a></td>
                    <td><a href="?order=impressions&sortOrder=<?= (isset($_GET['order']) && $_GET['order'] == 'impressions' && $_GET['sortOrder'] == 'desc') ? 'asc' : 'desc' ?>">Impressions </a></td>
                    <td><a href="?order=conversions&sortOrder=<?= (isset($_GET['order']) && $_GET['order'] == 'conversions' && $_GET['sortOrder'] == 'desc') ? 'asc' : 'desc' ?>">Conversions</a></td>
                    <td><a href="?order=cost&sortOrder=<?= (isset($_GET['order']) && $_GET['order'] == 'cost' && $_GET['sortOrder'] == 'desc') ? 'asc' : 'desc' ?>">Cost</a></td>
                    <td><a href="?order=revenue&sortOrder=<?= (isset($_GET['order']) && $_GET['order'] == 'revenue' && $_GET['sortOrder'] == 'desc') ? 'asc' : 'desc' ?>">Revenue</a></td>
                    <td><a href="?order=cv&sortOrder=<?= (isset($_GET['order']) && $_GET['order'] == 'cv' && $_GET['sortOrder'] == 'desc') ? 'asc' : 'desc' ?>">CV</a></td>
                    <td><a href="?order=roi&sortOrder=<?= (isset($_GET['order']) && $_GET['order'] == 'roi' && $_GET['sortOrder'] == 'desc') ? 'asc' : 'desc' ?>">ROI</a></td>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($completedData as $row): ?>
                    <tr>
                        <td><?= $row['site'] ?></td>
                        <td><?= $row['impressions'] ?></td>
                        <td><?= $row['conversions'] ?></td>
                        <td><?= $row['cost'] ?></td>
                        <td><?= $row['revenue'] ?></td>
                        <td><?= $row['cv'] ?></td>
                        <td style="color: <?= $row['roi'] >= 0 ? 'green' : 'red';?>"><?= $row['roi'] ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="alert alert-warning">
                Ничего не найдено
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
</body>
</html>
