<?php
    session_start();

    if (!isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
        header('Location: auth.php');
        exit;
    }

    require __DIR__.'/../utils/Database.php';
    require __DIR__.'/../utils/CvVersion.php';

    $db = Database::getInstance(__DIR__ . '/../moncv.sqlite');

    $currentVersionId = cvVersionResolveCurrent($db);
    if ($currentVersionId === null) {
        header('Location: /admin/index.php');
        exit;
    }

    if ($_SERVER["REQUEST_METHOD"] == "POST") {

        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $destDir = __DIR__ . '/../images/custom';

            if (!is_dir($destDir)) {
                mkdir($destDir, 0755, true);
            }

            $imageFilename = 'me-' . $currentVersionId . '.png';
            move_uploaded_file($_FILES['image']['tmp_name'], $destDir . '/' . $imageFilename);

            $db->query("UPDATE information SET image_path = :image_path WHERE cv_version_id = :v", [
                'image_path' => $imageFilename,
                'v'          => $currentVersionId
            ]);
        }

        $firstname = $_POST['firstname'] ?? "";
        $lastname = $_POST['lastname'] ?? "";
        $email = $_POST['email'] ?? "";
        $telephone = $_POST['telephone'] ?? "";
        $jobTitle = $_POST['job_title'] ?? "";
        $birthdate = $_POST['birthdate'] ?? "";
        $linkedinUrl = $_POST['linkedin_url'] ?? "";


        $db->query("UPDATE information SET firstname = :firstname, lastname = :lastname, email = :email, phone = :phone, job_title = :job_title, birthdate = :birthdate, linkedin_url = :linkedin_url WHERE cv_version_id = :v", [
            'firstname'    => $firstname,
            'lastname'     => $lastname,
            'email'        => $email,
            'phone'        => $telephone,
            'job_title'    => $jobTitle,
            'birthdate'    => $birthdate,
            'linkedin_url' => $linkedinUrl,
            'v'            => $currentVersionId
        ]);

        $line = $_POST['line'] ?? "";
        $zipcode = $_POST['zipcode'] ?? "";
        $city = $_POST['city'] ?? "";
        $country = $_POST['country'] ?? "";


        $db->query("UPDATE address SET line = :line, zipcode = :zipcode, city = :city, country = :country WHERE cv_version_id = :v", [
            'line'   => $line,
            'zipcode'   => $zipcode,
            'city' => $city,
            'country' => $country,
            'v' => $currentVersionId
        ]);

    }

    $information = $db->query("SELECT * FROM information WHERE cv_version_id = :v", ['v' => $currentVersionId])->fetch();
    $address = $db->query("SELECT * FROM address WHERE cv_version_id = :v", ['v' => $currentVersionId])->fetch();


?>
    <!DOCTYPE html>
    <html lang="fr">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <title>Admin - DASHBOARD</title>

        <link rel="stylesheet" type="text/css" href="/admin/assets/css/styles.css" />
        <link rel="stylesheet" type="text/css" href="/admin/assets/css/bootstrap.css" />

        <style>
            #imageProfile {
                width: 200px;
                height: 200px;
            }
        </style>
    </head>
    <body>
        <?php require_once("includes/nav.php"); ?>
        <?php require_once("includes/aside.php"); ?>

        <main>

            <form action="/admin/information.php" method="post" enctype="multipart/form-data">

                <div class="row">
                    <div class="col-12 col-md-6">
                        <h2>Information</h2>
                        <br />

                        <div class="mb-3">
                            <img src="/images/custom/<?= $information['image_path'] ?>" alt="<?= $information['image_path'] ?>" id="imageProfile" />

                            <input type="file" name="image" id="image" />
                        </div>

                        <div class="mb-3">
                            <label for="firstname">Prénom</label>
                            <input type="text" value="<?= $information['firstname'] ?? ""; ?>" name="firstname" id="firstname" class="form-control" />
                        </div>

                        <div class="mb-3">
                            <label for="lastname">Nom de famille</label>
                            <input type="text" value="<?= $information['lastname'] ?? ""; ?>" name="lastname" id="lastname" class="form-control" />
                        </div>

                        <div class="mb-3">
                            <label for="job_title">Titre / Poste</label>
                            <input type="text" value="<?= $information['job_title'] ?? ""; ?>" name="job_title" id="job_title" class="form-control" placeholder="ex: Développeur Web & Python" />
                        </div>

                        <div class="mb-3">
                            <label for="email">Email</label>
                            <input type="email" value="<?= $information['email'] ?? ""; ?>" name="email" id="email" class="form-control" />
                        </div>

                        <div class="mb-3">
                            <label for="telephone">Téléphone</label>
                            <input type="tel" value="<?= $information['phone'] ?? ""; ?>" name="telephone" id="telephone" class="form-control" />
                        </div>

                        <div class="mb-3">
                            <label for="birthdate">Date de naissance</label>
                            <input type="date" value="<?= $information['birthdate'] ?? ""; ?>" name="birthdate" id="birthdate" class="form-control" />
                        </div>

                        <div class="mb-3">
                            <label for="linkedin_url">Lien LinkedIn</label>
                            <input type="url" value="<?= $information['linkedin_url'] ?? ""; ?>" name="linkedin_url" id="linkedin_url" class="form-control" placeholder="https://www.linkedin.com/in/..." />
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <h2>Adresse Postal</h2>
                        <br />

                        <div class="mb-3">
                            <label for="line">Adresse</label>
                            <input type="text" value="<?= $address['line'] ?? ""; ?>" name="line" id="line" class="form-control" />
                        </div>

                        <div class="mb-3">
                            <label for="zipcode">Code Postal</label>
                            <input type="text" value="<?= $address['zipcode'] ?? ""; ?>" name="zipcode" id="zipcode" class="form-control" />
                        </div>

                        <div class="mb-3">
                            <label for="city">Ville</label>
                            <input type="text" value="<?= $address['city'] ?? ""; ?>" name="city" id="city" class="form-control" />
                        </div>

                        <div class="mb-3">
                            <label for="country">Pays</label>
                            <input type="text" value="<?= $address['country'] ?? ""; ?>" name="country" id="country" class="form-control" />
                        </div>
                    </div>
                </div>

                <div>
                    <input type="submit" value="Valider" class="btn btn-primary" />
                    <input type="reset" value="Reset" class="btn btn-danger" />
                </div>
            </form>


        </main>

        <script src="/admin/assets/js/scripts.js?time=<?= time(); ?>"></script>
    </body>
    </html>
<?php
