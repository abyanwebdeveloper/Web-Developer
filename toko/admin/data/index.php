<?php
// Penjaga folder data admin: larang akses direktori langsung.
http_response_code(403);
header('Content-Type: application/json; charset=utf-8');
echo json_encode(['ok' => false, 'message' => 'Akses langsung ke folder data tidak diizinkan.']);
