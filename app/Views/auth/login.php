<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
</head>

<body>
    <?=form_open('login', ['novalidate' => true])?>
    <div class="mb-3">
        <label for="email_text">E-mail</label>
        <input type="email" name="email_text" id="email_text">
    </div>

    <div class="mb-3">
        <label for="password_text">Palavra-passe</label>
        <input type="password" name="password_text" id="password_text">
    </div>


    <?= form_close();

    ?>

</body>

</html>