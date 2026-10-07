<?php

session_start();
require_once 'koneksi.php';

// ======================================================
// PROTEKSI ADMIN / OWNER
// ======================================================

if (
    !isset($_SESSION['user_id']) ||
    !in_array($_SESSION['role'] ?? '', ['admin', 'owner'])
) {
    header("Location: login.php");
    exit;
}


// ======================================================
// FUNGSI BANTU
// ======================================================

function cleanPrice($value)
{
    return (float) str_replace(
        ['.', ',', 'Rp', ' '],
        '',
        (string)$value
    );
}

function rupiah($value)
{
    return 'Rp ' . number_format(
        (float)$value,
        0,
        ',',
        '.'
    );
}


// ======================================================
// AJAX TAMBAH MEREK
// ======================================================

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['ajax_tambah_merek'])
) {
    header('Content-Type: application/json; charset=utf-8');

    $nama_merek = trim(
        $_POST['nama_merek'] ?? ''
    );

    if ($nama_merek === '') {
        echo json_encode([
            'success' => false,
            'message' => 'Nama merek wajib diisi.'
        ]);
        exit;
    }

    // Cek nama merek
    $cek = $koneksi->prepare("
        SELECT id, nama_merek, is_active
        FROM brands
        WHERE LOWER(TRIM(nama_merek)) = LOWER(TRIM(?))
        LIMIT 1
    ");

    $cek->bind_param(
        "s",
        $nama_merek
    );

    $cek->execute();

    $hasil = $cek->get_result();

    if ($hasil->num_rows > 0) {

        $merek_lama = $hasil->fetch_assoc();

        $cek->close();

        echo json_encode([
            'success' => false,
            'message' => 'Merek "' . $merek_lama['nama_merek'] . '" sudah tersedia.'
        ]);

        exit;
    }

    $cek->close();


    // Buat slug
    $slug = strtolower($nama_merek);

    $slug = preg_replace(
        '/[^a-z0-9]+/i',
        '-',
        $slug
    );

    $slug = trim(
        $slug,
        '-'
    );

    if ($slug === '') {
        $slug = 'merek-' . time();
    }

    $slug_awal = $slug;
    $nomor = 2;

    while (true) {

        $cek_slug = $koneksi->prepare("
            SELECT id
            FROM brands
            WHERE slug = ?
            LIMIT 1
        ");

        $cek_slug->bind_param(
            "s",
            $slug
        );

        $cek_slug->execute();

        $hasil_slug = $cek_slug->get_result();

        if ($hasil_slug->num_rows === 0) {

            $cek_slug->close();

            break;
        }

        $cek_slug->close();

        $slug =
            $slug_awal .
            '-' .
            $nomor;

        $nomor++;
    }


    // Simpan
    $stmt = $koneksi->prepare("
        INSERT INTO brands
        (
            nama_merek,
            slug,
            is_active
        )
        VALUES (?, ?, 1)
    ");

    $stmt->bind_param(
        "ss",
        $nama_merek,
        $slug
    );

    if ($stmt->execute()) {

        $id_baru = $stmt->insert_id;

        $stmt->close();

        echo json_encode([
            'success' => true,
            'message' => 'Merek berhasil ditambahkan.',
            'id' => $id_baru,
            'nama_merek' => $nama_merek
        ]);

    } else {

        echo json_encode([
            'success' => false,
            'message' => 'Gagal menambahkan merek.'
        ]);

        $stmt->close();
    }

    exit;
}


// ======================================================
// AJAX AMBIL DATA PRODUK UNTUK EDIT
// ======================================================

if (
    $_SERVER['REQUEST_METHOD'] === 'GET' &&
    ($_GET['ajax'] ?? '') === 'ambil_produk'
) {
    header('Content-Type: application/json; charset=utf-8');

    $id = (int)(
        $_GET['id'] ?? 0
    );

    if ($id <= 0) {

        echo json_encode([
            'success' => false,
            'message' => 'ID produk tidak valid.'
        ]);

        exit;
    }


    // Ambil produk
    $stmt = $koneksi->prepare("
        SELECT
            id,
            category_id,
            brand_id,
            kode_sku,
            nama_produk,
            deskripsi,
            harga,
            harga_pemasangan,
            stok,
            gambar,
            is_active,
            is_featured
        FROM products
        WHERE id = ?
        LIMIT 1
    ");

    $stmt->bind_param(
        "i",
        $id
    );

    $stmt->execute();

    $hasil = $stmt->get_result();

    if ($hasil->num_rows === 0) {

        $stmt->close();

        echo json_encode([
            'success' => false,
            'message' => 'Produk tidak ditemukan.'
        ]);

        exit;
    }

    $produk = $hasil->fetch_assoc();

    $stmt->close();


    // Ambil semua kategori
    $kategori_ids = [];

    $stmt_kat = $koneksi->prepare("
        SELECT category_id
        FROM product_categories
        WHERE product_id = ?
        ORDER BY category_id ASC
    ");

    $stmt_kat->bind_param(
        "i",
        $id
    );

    $stmt_kat->execute();

    $hasil_kat =
        $stmt_kat->get_result();

    while (
        $kat = $hasil_kat->fetch_assoc()
    ) {

        $kategori_ids[] =
            (int)$kat['category_id'];
    }

    $stmt_kat->close();


    // Fallback ke category_id lama
    if (
        empty($kategori_ids) &&
        !empty($produk['category_id'])
    ) {

        $kategori_ids[] =
            (int)$produk['category_id'];
    }


    echo json_encode([
        'success' => true,
        'produk' => [
            'id' => (int)$produk['id'],
            'category_id' => (int)$produk['category_id'],
            'brand_id' => (int)$produk['brand_id'],
            'kode_sku' => $produk['kode_sku'],
            'nama_produk' => $produk['nama_produk'],
            'deskripsi' => $produk['deskripsi'],
            'harga' => (float)$produk['harga'],
            'harga_pemasangan' =>
                $produk['harga_pemasangan'] !== null
                    ? (float)$produk['harga_pemasangan']
                    : null,
            'stok' => (int)$produk['stok'],
            'gambar' => $produk['gambar'],
            'is_active' => (int)$produk['is_active'],
            'is_featured' => (int)$produk['is_featured'],
            'kategori_ids' => $kategori_ids
        ]
    ]);

    exit;
}


// ======================================================
// PROSES TAMBAH / EDIT PRODUK
// ======================================================

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    (
        isset($_POST['simpan_produk']) ||
        isset($_POST['update_produk'])
    )
) {

    $mode_edit =
        isset($_POST['update_produk']);

    $product_id =
        (int)(
            $_POST['product_id'] ?? 0
        );


    // ==================================================
    // DATA DASAR
    // ==================================================

    $nama =
        trim(
            $_POST['nama_produk'] ?? ''
        );

    $deskripsi =
        trim(
            $_POST['deskripsi'] ?? ''
        );

    $brand_id =
        (int)(
            $_POST['brand_id'] ?? 0
        );

    $harga =
        cleanPrice(
            $_POST['harga'] ?? 0
        );

    $harga_pemasangan_input = trim(
        $_POST['harga_pemasangan'] ?? ''
    );

    $pakai_pemasangan =
        isset($_POST['pakai_pemasangan']) &&
        $_POST['pakai_pemasangan'] === '1';

    $harga_pemasangan = null;

    if ($pakai_pemasangan) {
        if ($harga_pemasangan_input !== '') {
            $harga_pemasangan = cleanPrice($harga_pemasangan_input);
        }
    }

    $stok =
        (int)(
            $_POST['stok'] ?? 0
        );

    $is_featured =
        isset($_POST['is_featured'])
            ? 1
            : 0;


    // ==================================================
    // KATEGORI
    // ==================================================

    $kategori_ids =
        $_POST['kategori_id'] ?? [];

    if (
        !is_array($kategori_ids)
    ) {

        $kategori_ids = [
            $kategori_ids
        ];
    }

    $kategori_ids =
        array_map(
            'intval',
            $kategori_ids
        );

    $kategori_ids =
        array_filter(
            $kategori_ids,
            function ($id) {
                return $id > 0;
            }
        );

    $kategori_ids =
        array_values(
            array_unique(
                $kategori_ids
            )
        );


    // ==================================================
    // VALIDASI
    // ==================================================

    $error = '';


    if (
        $mode_edit &&
        $product_id <= 0
    ) {

        $error =
            'ID produk tidak valid.';

    } elseif (
        $nama === ''
    ) {

        $error =
            'Nama produk wajib diisi.';

    } elseif (
        empty($kategori_ids)
    ) {

        $error =
            'Silakan pilih minimal satu kategori.';

    } elseif (
        $brand_id <= 0
    ) {

        $error =
            'Silakan pilih merek produk.';

    } elseif (
        $harga <= 0
    ) {

        $error =
            'Harga CCTV harus lebih dari 0.';

    } elseif (
        $pakai_pemasangan &&
        $harga_pemasangan === null
    ) {

        $error =
            'Harga pemasangan wajib diisi jika produk membutuhkan pemasangan.';

    } elseif (
        $pakai_pemasangan &&
        $harga_pemasangan <= 0
    ) {

        $error =
            'Harga pemasangan harus lebih dari 0.';

    } elseif (
        $pakai_pemasangan &&
        $harga_pemasangan < $harga
    ) {

        $error =
            'Harga CCTV + pemasangan tidak boleh lebih rendah dari harga CCTV.';

    } elseif (
        $stok < 0
    ) {

        $error =
            'Stok tidak boleh kurang dari 0.';
    }


    // ==================================================
    // CEK KATEGORI
    // ==================================================

    if ($error === '') {

        $stmt =
            $koneksi->prepare("
                SELECT id
                FROM categories
                WHERE id = ?
                LIMIT 1
            ");

        foreach (
            $kategori_ids
            as $kategori_id
        ) {

            $stmt->bind_param(
                "i",
                $kategori_id
            );

            $stmt->execute();

            $hasil =
                $stmt->get_result();

            if (
                $hasil->num_rows === 0
            ) {

                $error =
                    'Kategori yang dipilih tidak ditemukan.';

                break;
            }
        }

        $stmt->close();
    }


    // ==================================================
    // CEK BRAND
    // ==================================================

    if ($error === '') {

        $stmt =
            $koneksi->prepare("
                SELECT id
                FROM brands
                WHERE id = ?
                AND is_active = 1
                LIMIT 1
            ");

        $stmt->bind_param(
            "i",
            $brand_id
        );

        $stmt->execute();

        $hasil =
            $stmt->get_result();

        if (
            $hasil->num_rows === 0
        ) {

            $error =
                'Merek tidak ditemukan atau sedang nonaktif.';
        }

        $stmt->close();
    }


    // ==================================================
    // DATA GAMBAR
    // ==================================================

    $gambar_lama = '';

    if (
        $mode_edit &&
        $error === ''
    ) {

        $stmt =
            $koneksi->prepare("
                SELECT gambar
                FROM products
                WHERE id = ?
                LIMIT 1
            ");

        $stmt->bind_param(
            "i",
            $product_id
        );

        $stmt->execute();

        $hasil =
            $stmt->get_result();

        if (
            $hasil->num_rows === 0
        ) {

            $error =
                'Produk yang ingin diedit tidak ditemukan.';

        } else {

            $data_lama =
                $hasil->fetch_assoc();

            $gambar_lama =
                $data_lama['gambar'];
        }

        $stmt->close();
    }


    // ==================================================
    // UPLOAD GAMBAR
    // ==================================================

    $nama_file_baru = '';
    $file_baru_path = '';

    $ada_upload =
        isset($_FILES['gambar']) &&
        $_FILES['gambar']['error'] !== UPLOAD_ERR_NO_FILE;


    if (
        $error === '' &&
        $ada_upload
    ) {

        if (
            $_FILES['gambar']['error'] !== UPLOAD_ERR_OK
        ) {

            $error =
                'Gagal membaca gambar yang diunggah.';

        } else {

            $tmp =
                $_FILES['gambar']['tmp_name'];

            $nama_file =
                $_FILES['gambar']['name'];

            $ukuran =
                $_FILES['gambar']['size'];

            $ekstensi =
                strtolower(
                    pathinfo(
                        $nama_file,
                        PATHINFO_EXTENSION
                    )
                );


            $ekstensi_valid = [
                'jpg',
                'jpeg',
                'png'
            ];


            if (
                !in_array(
                    $ekstensi,
                    $ekstensi_valid
                )
            ) {

                $error =
                    'Format gambar harus JPG, JPEG, atau PNG.';

            } elseif (
                $ukuran > 5 * 1024 * 1024
            ) {

                $error =
                    'Ukuran gambar maksimal 5 MB.';

            } else {

                $mime =
                    mime_content_type($tmp);

                if (
                    !in_array(
                        $mime,
                        [
                            'image/jpeg',
                            'image/png'
                        ]
                    )
                ) {

                    $error =
                        'File yang diunggah bukan gambar yang valid.';

                } else {

                    if (
                        !is_dir('uploads')
                    ) {

                        mkdir(
                            'uploads',
                            0777,
                            true
                        );
                    }


                    $nama_file_baru =
                        'produk_' .
                        uniqid() .
                        '.' .
                        $ekstensi;

                    $file_baru_path =
                        'uploads/' .
                        $nama_file_baru;


                    if (
                        !move_uploaded_file(
                            $tmp,
                            $file_baru_path
                        )
                    ) {

                        $error =
                            'Gagal mengunggah gambar.';
                    }
                }
            }
        }
    }


    // ==================================================
    // JIKA ADA ERROR
    // ==================================================

    if ($error !== '') {

        $status =
            $mode_edit
                ? 'gagal_edit'
                : 'gagal_tambah';

        header(
            "Location: admin_produk.php?status=" .
            urlencode($status) .
            "&pesan=" .
            urlencode($error)
        );

        exit;
    }


    // ==================================================
    // SIMPAN KE DATABASE
    // ==================================================

    $koneksi->begin_transaction();

    try {

        // ==================================================
        // EDIT
        // ==================================================

        if ($mode_edit) {

            // Ambil SKU lama
            $stmt =
                $koneksi->prepare("
                    SELECT
                        kode_sku,
                        gambar
                    FROM products
                    WHERE id = ?
                    LIMIT 1
                ");

            $stmt->bind_param(
                "i",
                $product_id
            );

            $stmt->execute();

            $hasil =
                $stmt->get_result();

            if (
                $hasil->num_rows === 0
            ) {

                throw new Exception(
                    'Produk tidak ditemukan.'
                );
            }

            $produk_lama =
                $hasil->fetch_assoc();

            $stmt->close();


            $sku =
                $produk_lama['kode_sku'];

            $gambar_final =
                $produk_lama['gambar'];


            if (
                $nama_file_baru !== ''
            ) {

                $gambar_final =
                    $nama_file_baru;
            }


            // Kategori utama
            $kategori_utama =
                $kategori_ids[0];


            // Update products
            $stmt =
                $koneksi->prepare("
                    UPDATE products
                    SET
                        category_id = ?,
                        brand_id = ?,
                        nama_produk = ?,
                        deskripsi = ?,
                        harga = ?,
                        harga_pemasangan = ?,
                        stok = ?,
                        gambar = ?,
                        is_featured = ?
                    WHERE id = ?
                ");

            $stmt->bind_param(
                "iissddisii",
                $kategori_utama,
                $brand_id,
                $nama,
                $deskripsi,
                $harga,
                $harga_pemasangan,
                $stok,
                $gambar_final,
                $is_featured,
                $product_id
            );


            if (
                !$stmt->execute()
            ) {

                throw new Exception(
                    $stmt->error
                );
            }

            $stmt->close();


            // Hapus kategori lama
            $stmt =
                $koneksi->prepare("
                    DELETE FROM product_categories
                    WHERE product_id = ?
                ");

            $stmt->bind_param(
                "i",
                $product_id
            );

            if (
                !$stmt->execute()
            ) {

                throw new Exception(
                    $stmt->error
                );
            }

            $stmt->close();


            // Masukkan kategori baru
            $stmt =
                $koneksi->prepare("
                    INSERT INTO product_categories
                    (
                        product_id,
                        category_id
                    )
                    VALUES (?, ?)
                ");

            foreach (
                $kategori_ids
                as $kategori_id
            ) {

                $stmt->bind_param(
                    "ii",
                    $product_id,
                    $kategori_id
                );

                if (
                    !$stmt->execute()
                ) {

                    throw new Exception(
                        $stmt->error
                    );
                }
            }

            $stmt->close();


            // Commit
            $koneksi->commit();


            // Hapus gambar lama setelah database berhasil
            if (
                $nama_file_baru !== '' &&
                !empty($produk_lama['gambar'])
            ) {

                $gambar_lama_path =
                    'uploads/' .
                    $produk_lama['gambar'];

                if (
                    file_exists(
                        $gambar_lama_path
                    )
                ) {

                    @unlink(
                        $gambar_lama_path
                    );
                }
            }


            header(
                "Location: admin_produk.php?status=sukses_edit"
            );

            exit;

        }


        // ==================================================
        // TAMBAH
        // ==================================================

        else {

            $kategori_utama =
                $kategori_ids[0];


            // Generate SKU
            $sku =
                'SKU-' .
                date('YmdHis') .
                '-' .
                strtoupper(
                    substr(
                        bin2hex(
                            random_bytes(3)
                        ),
                        0,
                        6
                    )
                );


            // Insert product
            $stmt =
                $koneksi->prepare("
                    INSERT INTO products
                    (
                        category_id,
                        brand_id,
                        kode_sku,
                        nama_produk,
                        deskripsi,
                        harga,
                        harga_pemasangan,
                        stok,
                        gambar,
                        is_active,
                        is_featured
                    )
                    VALUES
                    (
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        1,
                        ?
                    )
                ");


            $stmt->bind_param(
                "iisssddisi",
                $kategori_utama,
                $brand_id,
                $sku,
                $nama,
                $deskripsi,
                $harga,
                $harga_pemasangan,
                $stok,
                $nama_file_baru,
                $is_featured
            );


            if (
                !$stmt->execute()
            ) {

                throw new Exception(
                    $stmt->error
                );
            }


            $product_id_baru =
                $stmt->insert_id;

            $stmt->close();


            // Simpan kategori
            $stmt =
                $koneksi->prepare("
                    INSERT INTO product_categories
                    (
                        product_id,
                        category_id
                    )
                    VALUES (?, ?)
                ");


            foreach (
                $kategori_ids
                as $kategori_id
            ) {

                $stmt->bind_param(
                    "ii",
                    $product_id_baru,
                    $kategori_id
                );

                if (
                    !$stmt->execute()
                ) {

                    throw new Exception(
                        $stmt->error
                    );
                }
            }

            $stmt->close();


            // Commit
            $koneksi->commit();


            header(
                "Location: admin_produk.php?status=sukses_tambah"
            );

            exit;
        }

    } catch (Exception $e) {

        $koneksi->rollback();


        // Hapus file baru jika database gagal
        if (
            $file_baru_path !== '' &&
            file_exists($file_baru_path)
        ) {

            @unlink(
                $file_baru_path
            );
        }


        header(
            "Location: admin_produk.php?status=gagal&pesan=" .
            urlencode(
                $e->getMessage()
            )
        );

        exit;
    }
}


// ======================================================
// STATUS PESAN
// ======================================================

$pesan_html = '';

$status =
    $_GET['status'] ?? '';

$pesan_url =
    $_GET['pesan'] ?? '';


if (
    $status === 'sukses_tambah'
) {

    $pesan_html = '
        <div class="bg-green-100 border border-green-200 text-green-700 p-4 rounded-xl mb-6 flex items-center gap-2">
            <i class="fa-solid fa-circle-check"></i>
            Produk berhasil ditambahkan.
        </div>
    ';

} elseif (
    $status === 'sukses_edit'
) {

    $pesan_html = '
        <div class="bg-green-100 border border-green-200 text-green-700 p-4 rounded-xl mb-6 flex items-center gap-2">
            <i class="fa-solid fa-circle-check"></i>
            Produk berhasil diperbarui.
        </div>
    ';

} elseif (
    $status === 'sukses_hapus'
) {

    $pesan_html = '
        <div class="bg-green-100 border border-green-200 text-green-700 p-4 rounded-xl mb-6 flex items-center gap-2">
            <i class="fa-solid fa-circle-check"></i>
            Produk berhasil dihapus.
        </div>
    ';

} elseif (
    $status === 'sukses_featured'
) {

    $pesan_html = '
        <div class="bg-yellow-100 border border-yellow-200 text-yellow-700 p-4 rounded-xl mb-6 flex items-center gap-2">
            <i class="fa-solid fa-star"></i>
            Status Produk Unggulan berhasil diperbarui.
        </div>
    ';

} elseif (
    $status === 'gagal' ||
    $status === 'gagal_edit' ||
    $status === 'gagal_tambah'
) {

    $pesan_html = '
        <div class="bg-red-100 border border-red-200 text-red-700 p-4 rounded-xl mb-6 flex items-center gap-2">
            <i class="fa-solid fa-circle-xmark"></i>
            ' .
            htmlspecialchars(
                $pesan_url ?: 'Terjadi kesalahan.'
            ) .
            '
        </div>
    ';
}


// ======================================================
// SEARCH & FILTER
// ======================================================

$search =
    trim(
        $_GET['search'] ?? ''
    );

$filter_kategori =
    (int)(
        $_GET['kategori'] ?? 0
    );

$filter_merek =
    (int)(
        $_GET['merek'] ?? 0
    );


// ======================================================
// QUERY PRODUK
// ======================================================

$sql = "
    SELECT
        p.*,

        GROUP_CONCAT(
            DISTINCT c.nama_kategori
            ORDER BY c.nama_kategori ASC
            SEPARATOR '|||'
        ) AS nama_kategori,

        GROUP_CONCAT(
            DISTINCT c.slug
            ORDER BY c.nama_kategori ASC
            SEPARATOR '|||'
        ) AS category_slugs,

        b.nama_merek

    FROM products p

    LEFT JOIN product_categories pc
        ON p.id = pc.product_id

    LEFT JOIN categories c
        ON pc.category_id = c.id

    LEFT JOIN brands b
        ON p.brand_id = b.id

    WHERE 1=1
";

$params = [];
$types = "";


// Search
if (
    $search !== ''
) {

    $sql .= "
        AND (
            p.nama_produk LIKE ?
            OR p.kode_sku LIKE ?
            OR p.deskripsi LIKE ?
            OR b.nama_merek LIKE ?

            OR EXISTS (
                SELECT 1
                FROM product_categories pc_search

                INNER JOIN categories c_search
                    ON pc_search.category_id = c_search.id

                WHERE pc_search.product_id = p.id
                AND c_search.nama_kategori LIKE ?
            )
        )
    ";

    $keyword =
        '%' . $search . '%';

    $params[] =
        $keyword;

    $params[] =
        $keyword;

    $params[] =
        $keyword;

    $params[] =
        $keyword;

    $params[] =
        $keyword;

    $types .=
        "sssss";
}


// Filter kategori
if (
    $filter_kategori > 0
) {

    $sql .= "
        AND EXISTS (
            SELECT 1
            FROM product_categories pc_filter
            WHERE pc_filter.product_id = p.id
            AND pc_filter.category_id = ?
        )
    ";

    $params[] =
        $filter_kategori;

    $types .=
        "i";
}


// Filter merek
if (
    $filter_merek > 0
) {

    $sql .= "
        AND p.brand_id = ?
    ";

    $params[] =
        $filter_merek;

    $types .=
        "i";
}


$sql .= "
    GROUP BY p.id
    ORDER BY p.id DESC
";


$stmt_produk =
    $koneksi->prepare(
        $sql
    );

if (
    !$stmt_produk
) {

    die(
        'Query produk gagal: ' .
        htmlspecialchars(
            $koneksi->error
        )
    );
}


if (
    !empty($params)
) {

    $stmt_produk->bind_param(
        $types,
        ...$params
    );
}


$stmt_produk->execute();

$query_produk =
    $stmt_produk->get_result();


// ======================================================
// KATEGORI
// ======================================================

$query_kategori =
    $koneksi->query("
        SELECT
            id,
            nama_kategori,
            slug
        FROM categories
        ORDER BY nama_kategori ASC
    ");


// ======================================================
// MEREK
// ======================================================

$query_merek =
    $koneksi->query("
        SELECT
            id,
            nama_merek,
            slug
        FROM brands
        WHERE is_active = 1
        ORDER BY nama_merek ASC
    ");


// ======================================================
// HITUNG PRODUK UNGGULAN
// ======================================================

$total_featured =
    $koneksi->query("
        SELECT COUNT(id) AS total
        FROM products
        WHERE is_active = 1
        AND is_featured = 1
    ")->fetch_assoc()['total'] ?? 0;


// ======================================================
// DATA UNTUK MODAL
// ======================================================

$merek_modal =
    $koneksi->query("
        SELECT
            id,
            nama_merek
        FROM brands
        WHERE is_active = 1
        ORDER BY nama_merek ASC
    ");

$kategori_modal =
    $koneksi->query("
        SELECT
            id,
            nama_kategori
        FROM categories
        ORDER BY nama_kategori ASC
    ");

?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Kelola Produk - BOSS CCTV
    </title>

    <script src="https://cdn.tailwindcss.com"></script>

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
    >

    <style>

        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f5f9;
        }

        ::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 10px;
        }

        .kategori-scroll::-webkit-scrollbar {
            width: 5px;
        }

    </style>

</head>


<body class="bg-gray-50 flex h-screen overflow-hidden font-sans">


<!-- ======================================================
     SIDEBAR
====================================================== -->

<aside class="w-64 bg-[#0f172a] text-gray-300 flex flex-col h-full shrink-0">

    <div class="h-16 flex items-center px-6 text-white font-bold border-b border-gray-800 gap-3">

        <i class="fa-solid fa-shield-halved text-blue-500"></i>

        POS Admin CCTV

    </div>


    <nav class="flex-1 px-4 py-6 space-y-2">

        <a
            href="admin_dashboard.php"
            class="flex items-center gap-3 px-4 py-3 hover:bg-gray-800 rounded-lg transition"
        >

            <i class="fa-solid fa-chart-line w-5 text-center"></i>

            Dashboard

        </a>


        <a
            href="admin_produk.php"
            class="flex items-center gap-3 px-4 py-3 bg-blue-600 text-white rounded-lg"
        >

            <i class="fa-solid fa-box w-5 text-center"></i>

            Kelola Stok & Produk

        </a>


        <a
            href="pesanan.php"
            class="flex items-center gap-3 px-4 py-3 hover:bg-gray-800 rounded-lg transition"
        >

            <i class="fa-solid fa-cart-shopping w-5 text-center"></i>

            Pesanan

        </a>

    </nav>


    <div class="p-4 border-t border-gray-800 space-y-2">

        <a
            href="index.php"
            class="flex items-center gap-3 px-4 py-2 hover:text-white"
        >

            <i class="fa-solid fa-globe w-5 text-center"></i>

            Lihat Website

        </a>


        <a
            href="logout.php"
            class="flex items-center gap-3 px-4 py-2 text-red-500 hover:text-red-400"
        >

            <i class="fa-solid fa-arrow-right-from-bracket w-5 text-center"></i>

            Keluar

        </a>

    </div>

</aside>


<!-- ======================================================
     MAIN
====================================================== -->

<main class="flex-1 overflow-y-auto p-8">

    <?= $pesan_html ?>


    <!-- HEADER -->

    <div class="flex flex-col md:flex-row md:justify-between md:items-end gap-4 mb-8">

        <div>

            <h1 class="text-2xl font-bold text-gray-900">
                Manajemen Produk
            </h1>

            <p class="text-sm text-gray-500 mt-1">
                Kelola produk, kategori, merek, harga, stok, dan Paket CCTV.
            </p>

        </div>


        <div class="bg-yellow-50 border border-yellow-200 rounded-xl px-5 py-3">

            <div class="flex items-center gap-3">

                <div class="w-10 h-10 bg-yellow-100 rounded-lg flex items-center justify-center">

                    <i class="fa-solid fa-star text-yellow-500"></i>

                </div>


                <div>

                    <p class="text-xs text-yellow-700">
                        Paket CCTV Aktif
                    </p>

                    <p class="text-xl font-bold text-yellow-800">
                        <?= (int)$total_featured ?>
                    </p>

                </div>

            </div>

        </div>

    </div>


    <!-- ==================================================
         FILTER
    ================================================== -->

    <form
        method="GET"
        action="admin_produk.php"
        id="formFilter"
        class="bg-white p-4 rounded-t-xl border border-gray-200 border-b-0"
    >

        <div class="flex flex-col md:flex-row gap-3 items-center justify-between">

            <div class="relative w-full md:w-72">

                <i class="fa-solid fa-magnifying-glass absolute left-3 top-3 text-gray-400"></i>

                <input
                    type="search"
                    name="search"
                    id="searchProduk"
                    value="<?= htmlspecialchars($search) ?>"
                    placeholder="Cari nama, SKU, kategori, merek..."
                    autocomplete="off"
                    class="w-full pl-10 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-blue-500"
                >

            </div>


            <div class="flex flex-wrap gap-2 w-full md:w-auto">

                <!-- KATEGORI -->

                <select
                    name="kategori"
                    id="filterKategori"
                    onchange="this.form.submit()"
                    class="border border-gray-200 rounded-lg px-4 py-2.5 text-sm bg-white focus:outline-none focus:border-blue-500"
                >

                    <option value="0">
                        Semua Kategori
                    </option>


                    <?php while (
                        $kat =
                        $query_kategori->fetch_assoc()
                    ): ?>

                        <option
                            value="<?= (int)$kat['id'] ?>"
                            <?= $filter_kategori == $kat['id'] ? 'selected' : '' ?>
                        >

                            <?= htmlspecialchars(
                                $kat['nama_kategori']
                            ) ?>

                        </option>

                    <?php endwhile; ?>

                </select>


                <!-- MEREK -->

                <select
                    name="merek"
                    id="filterMerek"
                    onchange="this.form.submit()"
                    class="border border-gray-200 rounded-lg px-4 py-2.5 text-sm bg-white focus:outline-none focus:border-blue-500"
                >

                    <option value="0">
                        Semua Merek
                    </option>


                    <?php while (
                        $brand =
                        $query_merek->fetch_assoc()
                    ): ?>

                        <option
                            value="<?= (int)$brand['id'] ?>"
                            <?= $filter_merek == $brand['id'] ? 'selected' : '' ?>
                        >

                            <?= htmlspecialchars(
                                $brand['nama_merek']
                            ) ?>

                        </option>

                    <?php endwhile; ?>

                </select>


                <?php if (
                    $search !== '' ||
                    $filter_kategori > 0 ||
                    $filter_merek > 0
                ): ?>

                    <a
                        href="admin_produk.php"
                        class="w-10 h-10 flex items-center justify-center bg-gray-100 hover:bg-red-50 hover:text-red-600 rounded-lg border border-gray-200"
                        title="Reset filter"
                    >

                        <i class="fa-solid fa-xmark"></i>

                    </a>

                <?php endif; ?>


                <!-- TAMBAH -->

                <button
                    type="button"
                    onclick="openTambahModal()"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-lg text-sm font-medium flex items-center gap-2"
                >

                    <i class="fa-solid fa-plus"></i>

                    Tambah Produk

                </button>

            </div>

        </div>

    </form>


    <!-- ==================================================
         TABLE
    ================================================== -->

    <div class="bg-white border border-gray-200 rounded-b-xl overflow-x-auto shadow-sm">

        <table class="w-full text-left border-collapse">

            <thead>

                <tr class="bg-gray-50 border-b border-gray-200 text-xs text-gray-500 uppercase tracking-wider">

                    <th class="px-6 py-4">
                        ID
                    </th>

                    <th class="px-6 py-4">
                        GAMBAR
                    </th>

                    <th class="px-6 py-4">
                        PRODUK
                    </th>

                    <th class="px-6 py-4">
                        SKU
                    </th>

                    <th class="px-6 py-4">
                        KATEGORI
                    </th>

                    <th class="px-6 py-4">
                        MEREK
                    </th>

                    <th class="px-6 py-4">
                        HARGA 
                    </th>

                    <th class="px-6 py-4">
                        HARGA + PASANG
                    </th>

                    <th class="px-6 py-4 text-center">
                        STOK
                    </th>

                    <th class="px-6 py-4 text-center">
                        PAKET CCTV
                    </th>

                    <th class="px-6 py-4 text-center">
                        STATUS
                    </th>

                    <th class="px-6 py-4 text-center">
                        AKSI
                    </th>

                </tr>

            </thead>


            <tbody class="divide-y divide-gray-100 text-sm">


                <?php if (
                    $query_produk &&
                    $query_produk->num_rows > 0
                ): ?>


                    <?php while (
                        $row =
                        $query_produk->fetch_assoc()
                    ): ?>

                        <?php

                        $data_search =
                            strtolower(
                                ($row['nama_produk'] ?? '') . ' ' .
                                ($row['kode_sku'] ?? '') . ' ' .
                                ($row['deskripsi'] ?? '') . ' ' .
                                ($row['nama_kategori'] ?? '') . ' ' .
                                ($row['nama_merek'] ?? '')
                            );


                        $daftar_kategori = [];

                        if (
                            !empty(
                                $row['nama_kategori']
                            )
                        ) {

                            $daftar_kategori =
                                array_filter(
                                    array_map(
                                        'trim',
                                        explode(
                                            '|||',
                                            $row['nama_kategori']
                                        )
                                    )
                                );
                        }

                        ?>


                        <tr
                            data-search="<?= htmlspecialchars($data_search, ENT_QUOTES, 'UTF-8') ?>"
                            class="hover:bg-gray-50 transition"
                        >

                            <!-- ID -->

                            <td class="px-6 py-4 text-gray-500">
                                #<?= (int)$row['id'] ?>
                            </td>


                            <!-- GAMBAR -->

                            <td class="px-6 py-4">

                                <div class="w-12 h-12 bg-gray-100 border rounded-lg overflow-hidden flex items-center justify-center">

                                    <?php if (
                                        !empty($row['gambar']) &&
                                        file_exists(
                                            'uploads/' .
                                            $row['gambar']
                                        )
                                    ): ?>

                                        <img
                                            src="uploads/<?= htmlspecialchars($row['gambar']) ?>"
                                            alt="<?= htmlspecialchars($row['nama_produk']) ?>"
                                            class="w-full h-full object-cover"
                                        >

                                    <?php else: ?>

                                        <i class="fa-solid fa-image text-gray-400"></i>

                                    <?php endif; ?>

                                </div>

                            </td>


                            <!-- PRODUK -->

                            <td class="px-6 py-4">

                                <div class="font-bold text-gray-900">

                                    <?= htmlspecialchars(
                                        $row['nama_produk']
                                    ) ?>

                                </div>

                                <div class="text-xs text-gray-500 max-w-xs truncate">

                                    <?= !empty($row['deskripsi'])
                                        ? htmlspecialchars(
                                            $row['deskripsi']
                                        )
                                        : 'Tidak ada deskripsi'
                                    ?>

                                </div>

                            </td>


                            <!-- SKU -->

                            <td class="px-6 py-4">

                                <span class="font-mono text-xs text-gray-600">

                                    <?= htmlspecialchars(
                                        $row['kode_sku']
                                    ) ?>

                                </span>

                            </td>


                            <!-- KATEGORI -->

                            <td class="px-6 py-4">

                                <?php if (
                                    !empty($daftar_kategori)
                                ): ?>

                                    <div class="flex flex-wrap gap-1.5 max-w-xs">

                                        <?php foreach (
                                            $daftar_kategori
                                            as $kategori
                                        ): ?>

                                            <span class="inline-flex bg-blue-50 text-blue-700 border border-blue-100 px-2.5 py-1 rounded-md text-xs font-medium">

                                                <?= htmlspecialchars(
                                                    $kategori
                                                ) ?>

                                            </span>

                                        <?php endforeach; ?>

                                    </div>

                                <?php else: ?>

                                    <span class="text-xs text-gray-400">
                                        Tanpa kategori
                                    </span>

                                <?php endif; ?>

                            </td>


                            <!-- MEREK -->

                            <td class="px-6 py-4">

                                <span class="inline-flex bg-purple-50 text-purple-700 border border-purple-100 px-2.5 py-1 rounded-md text-xs font-medium">

                                    <?= htmlspecialchars(
                                        $row['nama_merek'] ??
                                        'Tanpa Merek'
                                    ) ?>

                                </span>

                            </td>


                            <!-- HARGA -->

                            <td class="px-6 py-4 font-medium">

                                <?= rupiah(
                                    $row['harga']
                                ) ?>

                            </td>


                            <!-- HARGA PASANG -->

                            <td class="px-6 py-4 font-medium text-blue-700">

                                <?php if (
                                    $row['harga_pemasangan'] !== null &&
                                    $row['harga_pemasangan'] !== '' &&
                                    (float)$row['harga_pemasangan'] > 0
                                ): ?>

                                    <?= rupiah(
                                        $row['harga_pemasangan']
                                    ) ?>

                                <?php else: ?>

                                    <span class="text-gray-400">-</span>

                                <?php endif; ?>

                            </td>


                            <!-- STOK -->

                            <td class="px-6 py-4 text-center">

                                <?php if (
                                    (int)$row['stok'] <= 0
                                ): ?>

                                    <span class="text-red-600 font-bold">
                                        Habis
                                    </span>

                                <?php elseif (
                                    (int)$row['stok'] <= 5
                                ): ?>

                                    <span class="text-orange-500 font-bold">

                                        <?= (int)$row['stok'] ?>

                                    </span>

                                <?php else: ?>

                                    <span class="text-green-600 font-bold">

                                        <?= (int)$row['stok'] ?>

                                    </span>

                                <?php endif; ?>

                            </td>


                            <!-- FEATURED -->

                            <td class="px-6 py-4 text-center">

                                <?php if (
                                    (int)$row['is_featured'] === 1
                                ): ?>

                                    <a
                                        href="toggle_featured.php?id=<?= (int)$row['id'] ?>"
                                        onclick="return confirm('Hapus produk ini dari Produk Unggulan?')"
                                        class="inline-flex items-center gap-1 bg-yellow-100 text-yellow-700 border border-yellow-200 px-3 py-1.5 rounded-full text-xs font-semibold"
                                    >

                                        <i class="fa-solid fa-star"></i>

                                        Paket CCTV

                                    </a>

                                <?php else: ?>

                                    <a
                                        href="toggle_featured.php?id=<?= (int)$row['id'] ?>"
                                        onclick="return confirm('Jadikan produk ini Produk Unggulan?')"
                                        class="inline-flex items-center gap-1 bg-gray-100 text-gray-500 border border-gray-200 px-3 py-1.5 rounded-full text-xs font-semibold hover:bg-yellow-100 hover:text-yellow-700"
                                    >

                                        <i class="fa-regular fa-star"></i>

                                        Paket CCTV

                                    </a>

                                <?php endif; ?>

                            </td>


                            <!-- STATUS -->

                            <td class="px-6 py-4 text-center">

                                <?php if (
                                    (int)$row['is_active'] === 1
                                ): ?>

                                    <span class="bg-green-100 text-green-700 px-2.5 py-1 rounded-full text-xs font-medium">

                                        Aktif

                                    </span>

                                <?php else: ?>

                                    <span class="bg-gray-100 text-gray-500 px-2.5 py-1 rounded-full text-xs font-medium">

                                        Nonaktif

                                    </span>

                                <?php endif; ?>

                            </td>


                            <!-- AKSI -->

                            <td class="px-6 py-4 text-center whitespace-nowrap">

                                <button
                                    type="button"
                                    onclick="openEditModal(<?= (int)$row['id'] ?>)"
                                    class="text-gray-400 hover:text-blue-600 mx-1 transition"
                                    title="Edit Produk"
                                >

                                    <i class="fa-solid fa-pen-to-square"></i>

                                </button>


                                <a
                                    href="hapus_produk.php?id=<?= (int)$row['id'] ?>"
                                    onclick="return confirm('Apakah Anda yakin ingin menghapus produk ini?')"
                                    class="text-gray-400 hover:text-red-600 mx-1 transition"
                                    title="Hapus Produk"
                                >

                                    <i class="fa-solid fa-trash"></i>

                                </a>

                            </td>

                        </tr>

                    <?php endwhile; ?>


                    <!-- HASIL LIVE SEARCH -->

                    <tr
                        id="barisTidakDitemukan"
                        style="display:none;"
                    >

                        <td
                            colspan="12"
                            class="px-6 py-12 text-center"
                        >

                            <i class="fa-solid fa-box-open text-4xl text-gray-300"></i>

                            <p class="text-gray-500 font-medium mt-3">
                                Produk tidak ditemukan
                            </p>

                            <p class="text-xs text-gray-400 mt-1">
                                Coba gunakan kata kunci lain.
                            </p>

                        </td>

                    </tr>


                <?php else: ?>

                    <tr>

                        <td
                            colspan="12"
                            class="px-6 py-12 text-center"
                        >

                            <i class="fa-solid fa-box-open text-4xl text-gray-300"></i>

                            <p class="text-gray-500 font-medium mt-3">
                                Belum ada produk.
                            </p>

                        </td>

                    </tr>

                <?php endif; ?>

            </tbody>

        </table>

    </div>

</main>


<!-- ======================================================
     MODAL TAMBAH / EDIT PRODUK
====================================================== -->

<div
    id="modalProduk"
    class="fixed inset-0 z-50 hidden items-center justify-center p-4 bg-gray-900/60 backdrop-blur-sm opacity-0 transition-opacity duration-200"
>

    <div
        id="modalProdukContent"
        class="bg-white rounded-2xl shadow-2xl w-full max-w-4xl max-h-[94vh] overflow-y-auto transform scale-95 transition-transform duration-200"
    >

        <!-- HEADER MODAL -->

        <div class="sticky top-0 bg-white z-10 flex items-center justify-between px-6 py-4 border-b border-gray-100">

            <div>

                <h3
                    id="judulModalProduk"
                    class="text-lg font-bold text-gray-900"
                >
                    Tambah Produk Baru
                </h3>

                <p
                    id="subjudulModalProduk"
                    class="text-xs text-gray-500 mt-1"
                >
                    Tambahkan produk CCTV baru
                </p>

            </div>


            <button
                type="button"
                onclick="closeProdukModal()"
                class="w-9 h-9 rounded-lg hover:bg-gray-100 text-gray-400 hover:text-gray-700"
            >

                <i class="fa-solid fa-xmark text-xl"></i>

            </button>

        </div>


        <!-- FORM -->

        <form
            id="formProduk"
            action="admin_produk.php"
            method="POST"
            enctype="multipart/form-data"
            class="p-6"
        >

            <input
                type="hidden"
                name="product_id"
                id="editProductId"
                value=""
            >


            <!-- PENANDA MODE -->

            <input
                type="hidden"
                name="simpan_produk"
                id="inputModeTambah"
                value="1"
            >

            <input
                type="hidden"
                name="update_produk"
                id="inputModeEdit"
                value=""
            >


            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">


                <!-- ==================================================
                     KOLOM KIRI
                ================================================== -->

                <div class="space-y-5">


                    <!-- NAMA -->

                    <div>

                        <label class="block text-sm font-medium text-gray-700 mb-1">

                            Nama Produk

                            <span class="text-red-500">*</span>

                        </label>

                        <input
                            type="text"
                            name="nama_produk"
                            id="namaProduk"
                            maxlength="150"
                            required
                            class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                            placeholder="Contoh: CUE 2 3MP"
                        >

                    </div>


                    <!-- SKU -->

                    <div>

                        <label class="block text-sm font-medium text-gray-700 mb-1">

                            SKU

                        </label>

                        <input
                            type="text"
                            id="skuProduk"
                            readonly
                            class="w-full border border-gray-200 bg-gray-50 text-gray-500 rounded-lg px-4 py-2.5 text-sm"
                            placeholder="SKU otomatis dibuat saat produk ditambahkan"
                        >

                        <p
                            id="infoSku"
                            class="text-xs text-gray-400 mt-1"
                        >
                            SKU dibuat otomatis oleh sistem.
                        </p>

                    </div>


                    <!-- KATEGORI -->

                    <div>

                        <label class="block text-sm font-medium text-gray-700 mb-1">

                            Kategori

                            <span class="text-red-500">*</span>

                        </label>

                        <p class="text-xs text-gray-400 mb-2">

                            Produk dapat memiliki lebih dari satu kategori.

                        </p>


                        <div class="border border-gray-300 rounded-lg p-3 bg-gray-50 max-h-56 overflow-y-auto kategori-scroll">

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-1">

                                <?php while (
                                    $kat =
                                    $kategori_modal->fetch_assoc()
                                ): ?>

                                    <label class="flex items-center gap-2 p-2 rounded-lg hover:bg-white cursor-pointer">

                                        <input
                                            type="checkbox"
                                            name="kategori_id[]"
                                            value="<?= (int)$kat['id'] ?>"
                                            class="kategori-checkbox w-4 h-4 accent-blue-600"
                                        >

                                        <span class="text-sm text-gray-700">

                                            <?= htmlspecialchars(
                                                $kat['nama_kategori']
                                            ) ?>

                                        </span>

                                    </label>

                                <?php endwhile; ?>

                            </div>

                        </div>


                        <p
                            id="infoKategori"
                            class="text-xs text-red-500 mt-2"
                        >
                            Belum ada kategori dipilih.
                        </p>

                    </div>


                    <!-- MEREK -->

                    <div>

                        <label class="block text-sm font-medium text-gray-700 mb-1">

                            Merek

                            <span class="text-red-500">*</span>

                        </label>


                        <div class="flex gap-2">

                            <select
                                name="brand_id"
                                id="brandSelect"
                                required
                                class="flex-1 border border-gray-300 rounded-lg px-4 py-2.5 text-sm bg-white focus:outline-none focus:border-blue-500"
                            >

                                <option value="">
                                    -- Pilih Merek --
                                </option>


                                <?php while (
                                    $brand =
                                    $merek_modal->fetch_assoc()
                                ): ?>

                                    <option
                                        value="<?= (int)$brand['id'] ?>"
                                    >

                                        <?= htmlspecialchars(
                                            $brand['nama_merek']
                                        ) ?>

                                    </option>

                                <?php endwhile; ?>

                            </select>


                            <button
                                type="button"
                                onclick="openBrandModal()"
                                class="bg-purple-600 hover:bg-purple-700 text-white px-3 rounded-lg text-sm"
                                title="Tambah Merek"
                            >

                                <i class="fa-solid fa-plus"></i>

                            </button>

                        </div>

                    </div>


                    <!-- HARGA CCTV -->

                    <div>

                        <label class="block text-sm font-medium text-gray-700 mb-1">

                            Harga 

                            <span class="text-red-500">*</span>

                        </label>


                        <div class="relative">

                            <span class="absolute left-3 top-2.5 text-gray-500 text-sm">
                                Rp
                            </span>

                            <input
                                type="text"
                                name="harga"
                                id="harga"
                                inputmode="numeric"
                                autocomplete="off"
                                required
                                class="w-full border border-gray-300 rounded-lg pl-10 pr-4 py-2.5 text-sm focus:outline-none focus:border-blue-500"
                                placeholder="1.500.000"
                            >

                        </div>

                    </div>


                    <!-- HARGA PASANG -->

                    <div>

                        <div class="flex items-center justify-between mb-2">

                            <label class="block text-sm font-medium text-gray-700">
                                Harga + Pemasangan
                            </label>

                            <label class="inline-flex items-center gap-2 cursor-pointer">

                                <input
                                    type="checkbox"
                                    name="pakai_pemasangan"
                                    id="pakaiPemasangan"
                                    value="1"
                                    class="w-4 h-4 accent-blue-600"
                                >

                                <span class="text-xs font-medium text-gray-600">
                                    Butuh pemasangan teknisi
                                </span>

                            </label>

                        </div>


                        <div class="relative">

                            <span class="absolute left-3 top-2.5 text-gray-500 text-sm">
                                Rp
                            </span>

                            <input
                                type="text"
                                name="harga_pemasangan"
                                id="hargaPemasangan"
                                inputmode="numeric"
                                autocomplete="off"
                                readonly
                                class="w-full border border-gray-300 bg-gray-100 text-gray-400 rounded-lg pl-10 pr-4 py-2.5 text-sm focus:outline-none"
                                placeholder="Tidak diperlukan"
                            >

                        </div>

                        <p id="infoPemasangan" class="text-xs text-gray-400 mt-1">
                            Centang jika produk membutuhkan jasa pemasangan teknisi.
                        </p>

                    </div>


                    <!-- STOK -->

                    <div>

                        <label class="block text-sm font-medium text-gray-700 mb-1">

                            Stok

                            <span class="text-red-500">*</span>

                        </label>

                        <input
                            type="number"
                            name="stok"
                            id="stok"
                            min="0"
                            required
                            class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:border-blue-500"
                            placeholder="10"
                        >

                    </div>


                    <!-- FEATURED -->

                    <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-4">

                        <label class="flex gap-3 cursor-pointer">

                            <input
                                type="checkbox"
                                name="is_featured"
                                id="isFeatured"
                                value="1"
                                class="mt-1 w-4 h-4 accent-yellow-500"
                            >

                            <div>

                                <div class="font-semibold text-yellow-800">

                                    <i class="fa-solid fa-star mr-1"></i>

                                    Paket CCTV

                                </div>

                                <p class="text-xs text-yellow-700 mt-1">

                                    Tampilkan produk sebagai Paket CCTV.

                                </p>

                            </div>

                        </label>

                    </div>

                </div>


                <!-- ==================================================
                     KOLOM KANAN
                ================================================== -->

                <div class="space-y-5">


                    <!-- GAMBAR -->

                    <div>

                        <label class="block text-sm font-medium text-gray-700 mb-1">

                            Gambar Produk

                            <span
                                id="wajibGambar"
                                class="text-red-500"
                            >
                                *
                            </span>

                        </label>


                        <div
                            id="uploadBox"
                            onclick="document.getElementById('fileUpload').click()"
                            class="relative min-h-[230px] border-2 border-dashed border-gray-300 rounded-xl flex flex-col items-center justify-center text-center cursor-pointer hover:bg-gray-50 overflow-hidden"
                        >

                            <i
                                id="iconUpload"
                                class="fa-solid fa-cloud-arrow-up text-4xl text-gray-300 mb-3"
                            ></i>

                            <span
                                id="textUpload"
                                class="text-sm text-gray-500"
                            >
                                Klik untuk memilih gambar
                            </span>

                            <span class="text-xs text-gray-400 mt-1">
                                JPG / JPEG / PNG • Maks. 5 MB
                            </span>


                            <img
                                id="imagePreview"
                                src=""
                                alt="Preview"
                                class="hidden absolute inset-0 w-full h-full object-contain bg-white p-2"
                            >

                        </div>


                        <input
                            type="file"
                            name="gambar"
                            id="fileUpload"
                            accept=".jpg,.jpeg,.png,image/jpeg,image/png"
                            class="hidden"
                        >


                        <p
                            id="infoGambar"
                            class="text-xs text-gray-400 mt-2"
                        >
                            Pilih gambar produk.
                        </p>

                    </div>


                    <!-- DESKRIPSI -->

                    <div>

                        <label class="block text-sm font-medium text-gray-700 mb-1">

                            Deskripsi

                        </label>

                        <textarea
                            name="deskripsi"
                            id="deskripsi"
                            rows="8"
                            class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm resize-none focus:outline-none focus:border-blue-500"
                            placeholder="Masukkan spesifikasi dan deskripsi produk..."
                        ></textarea>

                    </div>


                    <!-- INFO -->

                    <div class="bg-blue-50 border border-blue-100 rounded-xl p-4">

                        <div class="flex gap-3">

                            <i class="fa-solid fa-circle-info text-blue-500 mt-0.5"></i>

                            <div class="text-xs text-blue-700">

                                <p class="font-semibold mb-1">
                                    Informasi
                                </p>

                                <p>
                                    Harga CCTV adalah harga perangkat.
                                </p>

                                <p class="mt-1">
                                    Harga + Pemasangan adalah harga perangkat yang sudah termasuk jasa pemasangan teknisi.
                                </p>

                                <p class="mt-1">
                                    Produk yang tidak membutuhkan teknisi tidak perlu mengisi harga pemasangan.
                                </p>

                                <p class="mt-1">
                                    Satu produk dapat memiliki beberapa kategori.
                                </p>

                            </div>

                        </div>

                    </div>

                </div>

            </div>


            <!-- FOOTER -->

            <div class="sticky bottom-0 bg-white mt-8 pt-4 border-t border-gray-100 flex justify-end gap-3">

                <button
                    type="button"
                    onclick="closeProdukModal()"
                    class="px-5 py-2.5 text-sm font-medium border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50"
                >

                    Batal

                </button>


                <button
                    type="submit"
                    id="btnSimpanProduk"
                    class="px-5 py-2.5 text-sm font-medium bg-blue-600 hover:bg-blue-700 text-white rounded-lg flex items-center gap-2"
                >

                    <i
                        id="iconSimpan"
                        class="fa-regular fa-floppy-disk"
                    ></i>

                    <span id="textSimpan">
                        Simpan Produk
                    </span>

                </button>

            </div>

        </form>

    </div>

</div>


<!-- ======================================================
     MODAL TAMBAH MEREK
====================================================== -->

<div
    id="modalBrand"
    class="fixed inset-0 z-[70] hidden items-center justify-center p-4 bg-gray-900/60 backdrop-blur-sm opacity-0 transition-opacity"
>

    <div
        id="modalBrandContent"
        class="bg-white rounded-2xl shadow-2xl w-full max-w-md transform scale-95 transition-transform"
    >

        <div class="flex justify-between items-center px-6 py-4 border-b">

            <div>

                <h3 class="font-bold text-lg text-gray-900">
                    Tambah Merek
                </h3>

                <p class="text-xs text-gray-500 mt-1">
                    Tambahkan merek CCTV baru
                </p>

            </div>


            <button
                type="button"
                onclick="closeBrandModal()"
                class="text-gray-400 hover:text-gray-700"
            >

                <i class="fa-solid fa-xmark text-xl"></i>

            </button>

        </div>


        <form
            id="formBrand"
            class="p-6"
        >

            <label class="block text-sm font-medium text-gray-700 mb-2">

                Nama Merek

                <span class="text-red-500">*</span>

            </label>


            <input
                type="text"
                name="nama_merek"
                id="namaMerekBaru"
                required
                maxlength="100"
                autocomplete="off"
                class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:border-purple-500"
                placeholder="Contoh: Hikvision"
            >


            <div
                id="pesanBrand"
                class="hidden mt-4 p-3 rounded-lg text-sm"
            ></div>


            <div class="flex justify-end gap-3 mt-6 pt-4 border-t">

                <button
                    type="button"
                    onclick="closeBrandModal()"
                    class="px-4 py-2.5 border border-gray-300 rounded-lg text-sm"
                >
                    Batal
                </button>


                <button
                    type="submit"
                    id="btnSimpanBrand"
                    class="px-4 py-2.5 bg-purple-600 hover:bg-purple-700 text-white rounded-lg text-sm flex items-center gap-2"
                >

                    <i class="fa-solid fa-plus"></i>

                    Simpan Merek

                </button>

            </div>

        </form>

    </div>

</div>


<script>

// ======================================================
// ELEMENT
// ======================================================

const modalProduk =
    document.getElementById(
        'modalProduk'
    );

const modalProdukContent =
    document.getElementById(
        'modalProdukContent'
    );

const formProduk =
    document.getElementById(
        'formProduk'
    );

const modalBrand =
    document.getElementById(
        'modalBrand'
    );

const modalBrandContent =
    document.getElementById(
        'modalBrandContent'
    );


// ======================================================
// TAMBAH MODAL
// ======================================================

function openTambahModal() {

    formProduk.reset();

    document.getElementById(
        'editProductId'
    ).value = '';

    document.getElementById(
        'inputModeTambah'
    ).disabled = false;

    document.getElementById(
        'inputModeEdit'
    ).disabled = true;


    document.getElementById(
        'judulModalProduk'
    ).textContent =
        'Tambah Produk Baru';


    document.getElementById(
        'subjudulModalProduk'
    ).textContent =
        'Tambahkan produk CCTV baru';


    document.getElementById(
        'textSimpan'
    ).textContent =
        'Simpan Produk';


    document.getElementById(
        'iconSimpan'
    ).className =
        'fa-regular fa-floppy-disk';


    document.getElementById(
        'skuProduk'
    ).value = '';


    document.getElementById(
        'skuProduk'
    ).placeholder =
        'SKU otomatis dibuat saat disimpan';


    document.getElementById(
        'infoSku'
    ).textContent =
        'SKU dibuat otomatis oleh sistem.';


    document.getElementById(
        'wajibGambar'
    ).classList.remove(
        'hidden'
    );


    document.getElementById(
        'fileUpload'
    ).required = true;


    resetPreview();


    clearKategori();


    document.getElementById(
        'pakaiPemasangan'
    ).checked = false;

    updatePemasanganField();


    openProdukModal();
}


// ======================================================
// OPEN MODAL
// ======================================================

function openProdukModal() {

    modalProduk.classList.remove(
        'hidden'
    );

    modalProduk.classList.add(
        'flex'
    );


    setTimeout(
        function () {

            modalProduk.classList.remove(
                'opacity-0'
            );

            modalProdukContent.classList.remove(
                'scale-95'
            );

        },
        10
    );
}


// ======================================================
// CLOSE MODAL
// ======================================================

function closeProdukModal() {

    modalProduk.classList.add(
        'opacity-0'
    );

    modalProdukContent.classList.add(
        'scale-95'
    );


    setTimeout(
        function () {

            modalProduk.classList.add(
                'hidden'
            );

            modalProduk.classList.remove(
                'flex'
            );

        },
        200
    );
}


// ======================================================
// EDIT MODAL
// ======================================================

async function openEditModal(id) {

    if (!id) {
        return;
    }


    // Reset
    formProduk.reset();

    clearKategori();

    resetPreview();


    document.getElementById(
        'judulModalProduk'
    ).textContent =
        'Edit Produk';


    document.getElementById(
        'subjudulModalProduk'
    ).textContent =
        'Perbarui informasi produk CCTV';


    document.getElementById(
        'textSimpan'
    ).textContent =
        'Simpan Perubahan';


    document.getElementById(
        'iconSimpan'
    ).className =
        'fa-solid fa-pen-to-square';


    document.getElementById(
        'inputModeTambah'
    ).disabled = true;

    document.getElementById(
        'inputModeEdit'
    ).disabled = false;

    document.getElementById(
        'inputModeEdit'
    ).value = '1';


    document.getElementById(
        'fileUpload'
    ).required = false;


    document.getElementById(
        'wajibGambar'
    ).classList.add(
        'hidden'
    );


    // Tampilkan loading
    document.getElementById(
        'namaProduk'
    ).value =
        'Memuat data...';


    openProdukModal();


    try {

        const response =
            await fetch(
                'admin_produk.php?ajax=ambil_produk&id=' +
                encodeURIComponent(id)
            );


        const data =
            await response.json();


        if (
            !data.success
        ) {

            alert(
                data.message ||
                'Gagal mengambil data produk.'
            );

            closeProdukModal();

            return;
        }


        const p =
            data.produk;


        // ==================================================
        // ISI FORM
        // ==================================================

        document.getElementById(
            'editProductId'
        ).value =
            p.id;


        document.getElementById(
            'namaProduk'
        ).value =
            p.nama_produk;


        document.getElementById(
            'skuProduk'
        ).value =
            p.kode_sku;


        document.getElementById(
            'infoSku'
        ).textContent =
            'SKU tidak dapat diubah.';


        document.getElementById(
            'brandSelect'
        ).value =
            p.brand_id;


        document.getElementById(
            'harga'
        ).value =
            formatRupiahInput(
                p.harga
            );


        const adaPemasangan =
            p.harga_pemasangan !== null &&
            p.harga_pemasangan !== '' &&
            Number(p.harga_pemasangan) > 0;

        document.getElementById(
            'pakaiPemasangan'
        ).checked = adaPemasangan;

        document.getElementById(
            'hargaPemasangan'
        ).value = adaPemasangan
            ? formatRupiahInput(p.harga_pemasangan)
            : '';

        updatePemasanganField();


        document.getElementById(
            'stok'
        ).value =
            p.stok;


        document.getElementById(
            'deskripsi'
        ).value =
            p.deskripsi || '';


        document.getElementById(
            'isFeatured'
        ).checked =
            p.is_featured == 1;


        // ==================================================
        // KATEGORI
        // ==================================================

        const kategori =
            p.kategori_ids || [];


        document
            .querySelectorAll(
                '.kategori-checkbox'
            )
            .forEach(
                function (checkbox) {

                    checkbox.checked =
                        kategori.includes(
                            parseInt(
                                checkbox.value
                            )
                        );

                }
            );


        updateInfoKategori();


        // ==================================================
        // GAMBAR
        // ==================================================

        if (
            p.gambar
        ) {

            const preview =
                document.getElementById(
                    'imagePreview'
                );

            preview.src =
                'uploads/' +
                p.gambar;

            preview.classList.remove(
                'hidden'
            );


            document.getElementById(
                'iconUpload'
            ).classList.add(
                'hidden'
            );


            document.getElementById(
                'textUpload'
            ).classList.add(
                'hidden'
            );


            document.getElementById(
                'infoGambar'
            ).textContent =
                'Gambar saat ini. Pilih gambar baru jika ingin menggantinya.';

        } else {

            document.getElementById(
                'infoGambar'
            ).textContent =
                'Produk belum memiliki gambar.';
        }


    } catch (error) {

        console.error(
            error
        );

        alert(
            'Terjadi kesalahan saat mengambil data produk.'
        );

        closeProdukModal();
    }
}


// ======================================================
// CLEAR KATEGORI
// ======================================================

function clearKategori() {

    document
        .querySelectorAll(
            '.kategori-checkbox'
        )
        .forEach(
            function (checkbox) {

                checkbox.checked =
                    false;

            }
        );


    updateInfoKategori();
}


// ======================================================
// INFO KATEGORI
// ======================================================

function updateInfoKategori() {

    const jumlah =
        document.querySelectorAll(
            '.kategori-checkbox:checked'
        ).length;


    const info =
        document.getElementById(
            'infoKategori'
        );


    if (
        jumlah === 0
    ) {

        info.textContent =
            'Belum ada kategori dipilih.';

        info.className =
            'text-xs text-red-500 mt-2';

    } else {

        info.textContent =
            jumlah +
            ' kategori dipilih.';

        info.className =
            'text-xs text-blue-600 mt-2';
    }
}


document
    .querySelectorAll(
        '.kategori-checkbox'
    )
    .forEach(
        function (checkbox) {

            checkbox.addEventListener(
                'change',
                updateInfoKategori
            );

        }
    );


// ======================================================
// HARGA PEMASANGAN OPSIONAL
// ======================================================

function updatePemasanganField() {

    const checkbox =
        document.getElementById('pakaiPemasangan');

    const input =
        document.getElementById('hargaPemasangan');

    const info =
        document.getElementById('infoPemasangan');

    if (!checkbox || !input || !info) {
        return;
    }

    if (checkbox.checked) {

        input.readOnly = false;
        input.required = true;
        input.placeholder = '2.000.000';

        input.classList.remove(
            'bg-gray-100',
            'text-gray-400',
            'cursor-not-allowed'
        );

        input.classList.add(
            'bg-white',
            'text-gray-700'
        );

        info.textContent =
            'Masukkan harga total perangkat + jasa pemasangan teknisi.';

        info.className =
            'text-xs text-blue-600 mt-1';

    } else {

        input.readOnly = true;
        input.required = false;
        input.value = '';
        input.placeholder = 'Tidak diperlukan';

        input.classList.remove(
            'bg-white',
            'text-gray-700'
        );

        input.classList.add(
            'bg-gray-100',
            'text-gray-400',
            'cursor-not-allowed'
        );

        info.textContent =
            'Produk ini tidak membutuhkan jasa pemasangan teknisi.';

        info.className =
            'text-xs text-gray-400 mt-1';
    }
}


document
    .getElementById('pakaiPemasangan')
    .addEventListener(
        'change',
        updatePemasanganField
    );


// ======================================================
// FORMAT HARGA
// ======================================================

function formatRupiahInput(
    angka
) {

    angka =
        String(
            angka
        ).replace(
            /\D/g,
            ''
        );


    if (
        angka === ''
    ) {

        return '';
    }


    return angka.replace(
        /\B(?=(\d{3})+(?!\d))/g,
        '.'
    );
}


function pasangFormatHarga(
    input
) {

    input.addEventListener(
        'input',
        function () {

            let angka =
                this.value.replace(
                    /\D/g,
                    ''
                );


            if (
                angka === ''
            ) {

                this.value =
                    '';

                return;
            }


            this.value =
                formatRupiahInput(
                    angka
                );
        }
    );
}


pasangFormatHarga(
    document.getElementById(
        'harga'
    )
);


pasangFormatHarga(
    document.getElementById(
        'hargaPemasangan'
    )
);


// ======================================================
// SUBMIT FORM
// ======================================================

formProduk.addEventListener(
    'submit',
    function (event) {

        const kategoriTerpilih =
            document.querySelectorAll(
                '.kategori-checkbox:checked'
            );


        if (
            kategoriTerpilih.length === 0
        ) {

            event.preventDefault();

            alert(
                'Silakan pilih minimal satu kategori.'
            );

            return;
        }


        const harga =
            parseInt(
                document
                    .getElementById(
                        'harga'
                    )
                    .value
                    .replace(
                        /\D/g,
                        ''
                    ),
                10
            ) || 0;


        const pakaiPemasangan =
            document.getElementById(
                'pakaiPemasangan'
            ).checked;

        const hargaPemasangan =
            parseInt(
                document
                    .getElementById(
                        'hargaPemasangan'
                    )
                    .value
                    .replace(
                        /\D/g,
                        ''
                    ),
                10
            ) || 0;


        if (
            harga <= 0
        ) {

            event.preventDefault();

            alert(
                'Harga CCTV harus lebih dari 0.'
            );

            return;
        }


        if (pakaiPemasangan) {

            if (hargaPemasangan <= 0) {

                event.preventDefault();

                alert(
                    'Harga pemasangan wajib diisi.'
                );

                return;
            }

            if (hargaPemasangan < harga) {

                event.preventDefault();

                alert(
                    'Harga CCTV + pemasangan tidak boleh lebih rendah dari harga CCTV.'
                );

                return;
            }
        }


        // Bersihkan format harga sebelum dikirim
        document.getElementById(
            'harga'
        ).value =
            harga;


        if (pakaiPemasangan) {
            document.getElementById(
                'hargaPemasangan'
            ).value =
                hargaPemasangan;
        } else {
            document.getElementById(
                'hargaPemasangan'
            ).value = '';
        }


        // Loading
        document.getElementById(
            'btnSimpanProduk'
        ).disabled =
            true;

        document.getElementById(
            'btnSimpanProduk'
        ).classList.add(
            'opacity-70'
        );

        document.getElementById(
            'textSimpan'
        ).textContent =
            'Menyimpan...';

        document.getElementById(
            'iconSimpan'
        ).className =
            'fa-solid fa-spinner fa-spin';
    }
);


// ======================================================
// PREVIEW GAMBAR
// ======================================================

document
    .getElementById(
        'fileUpload'
    )
    .addEventListener(
        'change',
        function () {

            const file =
                this.files[0];


            if (!file) {
                return;
            }


            if (
                file.size >
                5 * 1024 * 1024
            ) {

                alert(
                    'Ukuran gambar maksimal 5 MB.'
                );

                this.value =
                    '';

                return;
            }


            const reader =
                new FileReader();


            reader.onload =
                function (event) {

                    const preview =
                        document.getElementById(
                            'imagePreview'
                        );

                    preview.src =
                        event.target.result;

                    preview.classList.remove(
                        'hidden'
                    );


                    document.getElementById(
                        'iconUpload'
                    ).classList.add(
                        'hidden'
                    );


                    document.getElementById(
                        'textUpload'
                    ).classList.add(
                        'hidden'
                    );


                    document.getElementById(
                        'infoGambar'
                    ).textContent =
                        'Gambar baru dipilih.';

                };


            reader.readAsDataURL(
                file
            );

        }
    );


// ======================================================
// RESET PREVIEW
// ======================================================

function resetPreview() {

    const preview =
        document.getElementById(
            'imagePreview'
        );


    preview.src =
        '';

    preview.classList.add(
        'hidden'
    );


    document.getElementById(
        'iconUpload'
    ).classList.remove(
        'hidden'
    );


    document.getElementById(
        'textUpload'
    ).classList.remove(
        'hidden'
    );


    document.getElementById(
        'infoGambar'
    ).textContent =
        'Pilih gambar produk.';


    document.getElementById(
        'fileUpload'
    ).value =
        '';
}


// ======================================================
// MODAL BRAND
// ======================================================

function openBrandModal() {

    document.getElementById(
        'namaMerekBaru'
    ).value =
        '';


    document.getElementById(
        'pesanBrand'
    ).className =
        'hidden mt-4 p-3 rounded-lg text-sm';


    modalBrand.classList.remove(
        'hidden'
    );

    modalBrand.classList.add(
        'flex'
    );


    setTimeout(
        function () {

            modalBrand.classList.remove(
                'opacity-0'
            );

            modalBrandContent.classList.remove(
                'scale-95'
            );


            document.getElementById(
                'namaMerekBaru'
            ).focus();

        },
        10
    );
}


function closeBrandModal() {

    modalBrand.classList.add(
        'opacity-0'
    );

    modalBrandContent.classList.add(
        'scale-95'
    );


    setTimeout(
        function () {

            modalBrand.classList.add(
                'hidden'
            );

            modalBrand.classList.remove(
                'flex'
            );

        },
        200
    );
}


// ======================================================
// AJAX TAMBAH BRAND
// ======================================================

document
    .getElementById(
        'formBrand'
    )
    .addEventListener(
        'submit',
        async function (event) {

            event.preventDefault();


            const nama =
                document.getElementById(
                    'namaMerekBaru'
                ).value.trim();


            if (
                nama === ''
            ) {

                tampilkanPesanBrand(
                    'Nama merek wajib diisi.',
                    false
                );

                return;
            }


            const button =
                document.getElementById(
                    'btnSimpanBrand'
                );


            button.disabled =
                true;

            button.innerHTML = `
                <i class="fa-solid fa-spinner fa-spin"></i>
                Menyimpan...
            `;


            const formData =
                new FormData();


            formData.append(
                'ajax_tambah_merek',
                '1'
            );


            formData.append(
                'nama_merek',
                nama
            );


            try {

                const response =
                    await fetch(
                        'admin_produk.php',
                        {
                            method: 'POST',
                            body: formData
                        }
                    );


                const data =
                    await response.json();


                if (
                    data.success
                ) {

                    const select =
                        document.getElementById(
                            'brandSelect'
                        );


                    const option =
                        document.createElement(
                            'option'
                        );


                    option.value =
                        data.id;

                    option.textContent =
                        data.nama_merek;


                    select.appendChild(
                        option
                    );


                    select.value =
                        data.id;


                    tampilkanPesanBrand(
                        data.message,
                        true
                    );


                    setTimeout(
                        function () {

                            closeBrandModal();

                        },
                        500
                    );

                } else {

                    tampilkanPesanBrand(
                        data.message ||
                        'Gagal menambahkan merek.',
                        false
                    );
                }

            } catch (error) {

                console.error(
                    error
                );

                tampilkanPesanBrand(
                    'Terjadi kesalahan pada server.',
                    false
                );
            }


            button.disabled =
                false;

            button.innerHTML = `
                <i class="fa-solid fa-plus"></i>
                Simpan Merek
            `;

        }
    );


// ======================================================
// PESAN BRAND
// ======================================================

function tampilkanPesanBrand(
    pesan,
    berhasil
) {

    const box =
        document.getElementById(
            'pesanBrand'
        );


    box.textContent =
        pesan;


    box.className =
        berhasil
            ? 'mt-4 p-3 rounded-lg text-sm bg-green-100 border border-green-200 text-green-700'
            : 'mt-4 p-3 rounded-lg text-sm bg-red-100 border border-red-200 text-red-700';

}


// ======================================================
// KLIK LUAR MODAL PRODUK
// ======================================================

modalProduk.addEventListener(
    'click',
    function (event) {

        if (
            event.target === modalProduk
        ) {

            closeProdukModal();

        }

    }
);


// ======================================================
// KLIK LUAR MODAL BRAND
// ======================================================

modalBrand.addEventListener(
    'click',
    function (event) {

        if (
            event.target === modalBrand
        ) {

            closeBrandModal();

        }

    }
);


// ======================================================
// ESC
// ======================================================

document.addEventListener(
    'keydown',
    function (event) {

        if (
            event.key !== 'Escape'
        ) {

            return;
        }


        if (
            !modalBrand.classList.contains(
                'hidden'
            )
        ) {

            closeBrandModal();

        } else if (
            !modalProduk.classList.contains(
                'hidden'
            )
        ) {

            closeProdukModal();

        }

    }
);

</script>


<script>

// ======================================================
// LIVE SEARCH
// ======================================================

document.addEventListener(
    'DOMContentLoaded',
    function () {

        const search =
            document.getElementById(
                'searchProduk'
            );


        const rows =
            Array.from(
                document.querySelectorAll(
                    'tbody tr[data-search]'
                )
            );


        const emptyRow =
            document.getElementById(
                'barisTidakDitemukan'
            );


        if (
            !search
        ) {
            return;
        }


        function filterRows() {

            const keyword =
                search.value
                    .toLowerCase()
                    .trim();


            let jumlah =
                0;


            rows.forEach(
                function (row) {

                    const text =
                        (
                            row.dataset.search ||
                            ''
                        ).toLowerCase();


                    const cocok =
                        keyword === '' ||
                        text.includes(
                            keyword
                        );


                    row.style.display =
                        cocok
                            ? ''
                            : 'none';


                    if (
                        cocok
                    ) {

                        jumlah++;
                    }

                }
            );


            if (
                emptyRow
            ) {

                emptyRow.style.display =
                    jumlah === 0
                        ? 'table-row'
                        : 'none';
            }
        }


        search.addEventListener(
            'input',
            filterRows
        );


        search.addEventListener(
            'search',
            filterRows
        );


        // Jangan submit/reload saat Enter
        document
            .getElementById(
                'formFilter'
            )
            .addEventListener(
                'submit',
                function (event) {

                    event.preventDefault();

                    filterRows();

                }
            );


        filterRows();

    }
);

</script>

</body>
</html>