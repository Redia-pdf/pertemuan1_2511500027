<?php

require_once "../config.php";
require_once "../helpers/response.php";

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        //Get by id
        //mahasiswa.php?id=1
        if (isset($_GET['id'])) {
            $id = $_GET['id'];

            $q = "SELECT m.id, m.nama, m.nim, j.nama_jurusan AS jurusan FROM mahasiswa m LEFT JOIN jurusan j on m.jurusan_id = j.id WHERE m.id = $id";

            $r = mysqli_query($koneksi, $q);

            if (!$r) {
                sendResponse(
                    false,
                    "Query gagal : " . mysqli_error($koneksi),
                    null,
                    500
                );
            }

            $data = mysqli_fetch_assoc($r);

            if (!$data) {
                sendResponse(
                    false,
                    "Mahasiswa tidak ditemukan",
                    null,
                    404
                );
            }

            sendResponse(
                true,
                "Berhasil",
                $data,
                200
            );
        }

        //search
        //api/mahasiswa.php?search=a
        if (isset($_GET['search'])) {

            $search = $_GET['search'];

            $q = "SELECT m.id, m.nama, m.nim, j.nama_jurusan AS jurusan FROM mahasiswa m LEFT JOIN jurusan j on m.jurusan_id = j.id WHERE m.nama LIKE '%$search%' OR m.nim LIKE '%$search%' ORDER BY m.id DESC";

            $r = mysqli_query($koneksi, $q);

            if (!$r) {
                sendResponse(
                    false,
                    "Query gagal : " . mysqli_error($koneksi),
                    null,
                    500
                );
            }

            $data = [];
            while ($row = mysqli_fetch_assoc($r)) {
                $data[] = $row;
            }

            sendResponse(
                true,
                "Berhasil",
                $data,
                200
            );
        }

        //limit & offset
        //pagination
        //api/mahasiswa.php?limit=2
        //api/mahasiswa.php?page=2
        if (isset($_GET['page']) || isset($_GET['limit'])) {

            $page = isset($_GET['page'])
                ? (int)$_GET['page']
                : 1;

            $limit = isset($_GET['limit'])
                ? (int)$_GET['limit']
                : 10;

            if ($page < 1) {
                $page = 1;
            }
            if ($limit < 1) {
                $limit = 10;
            }
            $offset = ($page - 1) * $limit;

            $q = "SELECT m.id, m.nama, m.nim, j.nama_jurusan AS jurusan FROM mahasiswa m LEFT JOIN jurusan j on m.jurusan_id = j.id ORDER BY m.id DESC LIMIT $limit OFFSET $offset";

            $r = mysqli_query($koneksi, $q);

            if (!$r) {
                sendResponse(
                    false,
                    "Query gagal : " . mysqli_error($koneksi),
                    null,
                    500
                );
            }

            $data = [];
            while ($row = mysqli_fetch_assoc($r)) {
                $data[] = $row;
            }

            sendResponse(
                true,
                "Berhasil",
                $data,
                200
            );
        }

        $q = "SELECT m.id, m.nama, m.nim, j.nama_jurusan AS jurusan FROM mahasiswa m LEFT JOIN jurusan j on m.jurusan_id = j.id ORDER BY m.id DESC";


        $r = mysqli_query($koneksi, $q);

        if (!$r) {
            sendResponse(
                false,
                "Query gagal : " . mysqli_error($koneksi),
                null,
                500
            );
        }

        $data = [];
        while ($row = mysqli_fetch_assoc($r)) {
            $data[] = $row;
        }

        sendResponse(
            true,
            "Berhasil",
            $data,
            200
        );
        break;

    case 'POST':
        $input = json_decode(file_get_contents('php://input'), true);

        //cek apakah input ada dan field nama, nim, jurusan_id ada
        if (!$input || !isset($input['nama']) || !isset($input['nim']) || !isset($input['jurusan_id'])) {
            sendResponse(
                false,
                "field nama, nim, jurusan_id Wajib diisi",
                null,
                400
            );
        }

        $nama = $input['nama'];
        $nim = $input['nim'];
        $jurusan_id = (int) $input['jurusan_id'];

        //cek jurusan apakah ada
        $cek = mysqli_query($koneksi, "SELECT id FROM jurusan WHERE id = $jurusan_id");
        if (mysqli_num_rows($cek) == 0) {
            sendResponse(false, "Jurusan tidak ditemukan", null, 404);
        }

        //cek nim apakah nim sudah ada belum di table mahasiswa
        $cek_nim = mysqli_query($koneksi, "SELECT id FROM mahasiswa WHERE nim = '$nim'");
        if (mysqli_num_rows($cek_nim) > 0) {
            sendResponse(false, "NIM sudah terdaftar", null, 400);
        }

        //insert data mahasiswa
        $q = "INSERT INTO mahasiswa (nama, nim, jurusan_id, created_at) VALUES ('$nama', '$nim', $jurusan_id, CURDATE())";
        if (mysqli_query($koneksi, $q)) {
            sendResponse(
                true,
                "data tersimpan",
                ["id" => mysqli_insert_id($koneksi)],
                201
            );
        } else {
            sendResponse(
                false,
                mysqli_error($koneksi),
                null,
                500
            );
        }
        break;

        case 'PUT':
            //put disini
        $input = json_decode(file_get_contents('php://input'), true);

        //cek apakah nama, nim, jurusan_id ada didalam body yang dikirim
        if (!$input || !isset($input['nama']) || !isset($input['nim']) || !isset($input['jurusan_id'])) {
            sendResponse(
                false,
                "field nama, nim, jurusan_id tidak boleh kosong",
                null,
                400
            );
        }

        $id = $_GET['id'];

        $nama = $input['nama'];
        $nim = $input['nim'];
        $jurusan_id = (int) $input['jurusan_id'];

        //cek apakah mahasiswa dengan id tersebut ada?
        $cek_mahasiswa = mysqli_query($koneksi, "SELECT id FROM  mahasiswa WHERE id = '$id'");
        //jika tidak ada error 404
        if (mysqli_num_rows($cek_mahasiswa) == 0) {
            sendResponse(
                false,
                "Mahasiswa tidak ditemukan",
                null,
                404
            );
        }

        //cek jurusan apakah ada
        $cek = mysqli_query($koneksi, "SELECT id FROM jurusan WHERE id = $jurusan_id");
        if (mysqli_num_rows($cek) == 0) {
            sendResponse(false, "Jurusan tidak ditemukan", null, 404);
        }

        //cek nim apakah nim sudah ada belum di table mahasiswa
        $cek_nim = mysqli_query($koneksi, "SELECT id FROM mahasiswa WHERE nim = '$nim' AND id != $id");
        if (mysqli_num_rows($cek_nim) > 0) {
            sendResponse(false, "NIM sudah terdaftar", null, 400);
        }

        //insert data mahasiswa
        $q = "UPDATE mahasiswa SET nama = '$nama', nim = '$nim', jurusan_id = $jurusan_id WHERE id = $id";
        if (mysqli_query($koneksi, $q)) {
            sendResponse(
                true,
                "data berhasil diperbarui",
                ["id" => $id ],
                200
            );
        } else {
            sendResponse(
                false,
                mysqli_error($koneksi),
                null,
                500
            );
        }
        break;
    case 'DELETE':
        //url : api/mahasiswa.php?id=<id mahasiswa yang ingin dihapus>
        //delete disini
        $id = $_GET['id'];

        //cek apakah mahasiswa dengan id tersebut ada?
        $cek_mahasiswa = mysqli_query($koneksi, "SELECT id FROM  mahasiswa WHERE id = '$id'");
        //jika tidak ada error 404
        if (mysqli_num_rows($cek_mahasiswa) == 0) {
            sendResponse(
                false,
                "Mahasiswa tidak ditemukan",
                null,
                404
            );
        }

        //query delete
        $q = "DELETE FROM mahasiswa WHERE id = '$id'";

         if (mysqli_query($koneksi, $q)) {
            sendResponse(
                true,
                "data berhasil dihapus",
                ["id" => $id ],
                200
            );
        } else {
            sendResponse(
                false,
                mysqli_error($koneksi),
                null,
                500
            );
        }

        //mysqli_response
    default:
        sendResponse(
            false,
            "Method tidak diizinkan",
            null,
            405
        );
        break;
}
