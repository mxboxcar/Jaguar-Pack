<?php
// ใช้: php7.4 tools/legacy-login-sim.php <username> <password>  → พิมพ์ PHPSESSID ที่ล็อกอินด้วยโค้ดเดิม (admin_jl_bench เท่านั้น)
// จำลอง system_check_login.php สาขา "success" ด้วยโค้ดเดิมจริง (encrypt + session handler เดิม) บน admin_jl_bench
// (endpoint จริงบน :8101 ใช้ไม่ได้: goLogMessage เขียน log ไปที่ webroot production แล้ว exit)
[$_, $username, $password] = $argv;
chdir('/home/admin/bench/jl-old');
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
ob_start();
require 'system_config.php';
ob_end_clean();
if (dbname !== 'admin_jl_bench') { fwrite(STDERR, "not bench\n"); exit(1); }
$sql = "SELECT * FROM `user` WHERE `username` = '".addslashes($username)."' AND `is_delete` = 0 AND `password` = '".encrypt($password)."' AND `is_enable` = '1' AND `is_approved` != '-1' ";
$data = mysqli_fetch_array(mysqlquerys($sql));
if (!$data) { fwrite(STDERR, "login failed\n"); exit(2); }
$_SESSION['user_id'] = encrypt($data['username'].'&'.$data['id']);
$_SESSION['user_role'] = encrypt($data['password']);
echo session_id(), PHP_EOL;
session_write_close();
