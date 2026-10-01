<?php
/** @var string $pageTitle */
/** @var string|null $bgImage */
$bgImage = $bgImage ?? 'images/plv.jpg';
$bodyStyle = '--page-bg: url(\'' . htmlspecialchars($bgImage, ENT_QUOTES) . '\')';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle) ?></title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <?php include __DIR__ . '/styles.php'; ?>
  <?php if (!empty($extraHead)) echo $extraHead; ?>
</head>
<body style="<?= $bodyStyle ?>">
