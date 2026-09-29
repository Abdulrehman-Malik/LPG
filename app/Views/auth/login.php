<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= esc($title ?? 'Login') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <style>
        :root {
            --lpg-dark: #1b2a3a;
            --lpg-accent: #ff7a1a;
        }
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            background: linear-gradient(135deg, var(--lpg-dark) 0%, #0f1924 100%);
        }
        .login-card {
            border: none;
            border-radius: 1rem;
            box-shadow: 0 1rem 3rem rgba(0, 0, 0, .3);
            overflow: hidden;
        }
        .login-brand {
            background: var(--lpg-dark);
            color: #fff;
            padding: 2.5rem 2rem;
        }
        .login-brand .bi {
            font-size: 2.5rem;
            color: var(--lpg-accent);
        }
        .btn-brand {
            background: var(--lpg-accent);
            border-color: var(--lpg-accent);
            color: #fff;
        }
        .btn-brand:hover {
            background: #e2680f;
            border-color: #e2680f;
            color: #fff;
        }
        .form-control:focus {
            border-color: var(--lpg-accent);
            box-shadow: 0 0 0 .2rem rgba(255, 122, 26, .15);
        }
    </style>
</head>
<body>
<div class="container">
    <div class="row justify-content-center">
        <div class="col-11 col-sm-9 col-md-7 col-lg-5">
            <div class="card login-card">
                <div class="login-brand text-center">
                    <i class="bi bi-fire"></i>
                    <h4 class="mt-2 mb-0 fw-bold">Perfect LPG (Pvt.) LTD</h4>
                    <small class="opacity-75">Distribution &amp; Cylinder Inventory System</small>
                </div>
                <div class="card-body p-4 p-md-5">

                    <?php if (session()->getFlashdata('error')) : ?>
                        <div class="alert alert-danger d-flex align-items-center" role="alert">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            <div><?= esc(session()->getFlashdata('error')) ?></div>
                        </div>
                    <?php endif; ?>

                    <?php if (session()->getFlashdata('success')) : ?>
                        <div class="alert alert-success d-flex align-items-center" role="alert">
                            <i class="bi bi-check-circle-fill me-2"></i>
                            <div><?= esc(session()->getFlashdata('success')) ?></div>
                        </div>
                    <?php endif; ?>

                    <?php if (session()->getFlashdata('errors')) : ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0 ps-3">
                                <?php foreach (session()->getFlashdata('errors') as $error) : ?>
                                    <li><?= esc($error) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form action="<?= site_url('login') ?>" method="post" novalidate>
                        <?= csrf_field() ?>

                        <div class="mb-3">
                            <label for="login" class="form-label">Username or Email</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white"><i class="bi bi-person"></i></span>
                                <input type="text" class="form-control" id="login" name="login"
                                       value="<?= esc(old('login')) ?>" placeholder="e.g. admin" required autofocus>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white"><i class="bi bi-lock"></i></span>
                                <input type="password" class="form-control" id="password" name="password"
                                       placeholder="••••••••" required>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="remember" name="remember" value="1">
                                <label class="form-check-label" for="remember">Remember me</label>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-brand w-100 py-2 fw-semibold">
                            <i class="bi bi-box-arrow-in-right me-1"></i> Sign In
                        </button>
                    </form>
                </div>
            </div>
            <p class="text-center text-white-50 mt-3 small mb-0">
                &copy; <?= date('Y') ?> Perfect LPG (Pvt.) LTD. All rights reserved.
            </p>
        </div>
    </div>
</div>
</body>
</html>
