<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Error <?php echo $code = isset($code) ? $code : 500; ?></title>
    <style>
        .sr-container {
            font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
            border: 1px solid #b3b3b3;
            padding: 30px 20px 50px;
            box-shadow: 0 1px 10px #a7a7a7, inset 0 1px 0 #fff;
            border-radius: 4px;
            max-width: 380px;
            width: 380px;
            margin: 0 auto;
        }
    </style>
</head>
<body>
<div class="sr-container">
    <h2 style="color: #D35780;margin: 0 10px;font-size: 30px;text-align: center;">
        <span style="color: #bbb; font-size: 60px;">
            <?php echo $code; ?>
        </span>
        <?php echo isset($title) ? $title : 'Server Error'; ?>
    </h2><br/>
    <p style="margin: 1em 0;">
        <?php echo isset($detail) ? $detail : 'Something went wrong.'; ?>
    </p>
</div>
</body>
</html>