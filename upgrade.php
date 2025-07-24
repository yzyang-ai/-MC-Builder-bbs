<?php
require_once 'config.php';

// 升级脚本 v1.0
// 上传新源码后，访问本文件即可自动升级数据库结构，数据不会丢失。
echo "<h2>论坛升级程序</h2>";

try {
    // 1. 检查数据库连接
    if (!$pdo) throw new Exception('数据库连接失败');

    // 2. 需要执行的升级SQL（请根据新版本database.sql补充）
    $sqls = [];

    // 示例：为 categories 表增加 icon 字段（如果不存在）
    $sqls[] = "ALTER TABLE categories ADD COLUMN icon VARCHAR(50) DEFAULT ''";
    // 示例：为 threads 表增加 example_field 字段（如有新字段可仿照添加）
    // $sqls[] = "ALTER TABLE threads ADD COLUMN example_field VARCHAR(255) NULL";

    // 自动创建 settings 表（如不存在）
    $sqls[] = "CREATE TABLE IF NOT EXISTS `settings` (
      `key` VARCHAR(50) PRIMARY KEY,
      `value` TEXT
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

    $success = [];
    $fail = [];
    foreach ($sqls as $sql) {
        try {
            $pdo->exec($sql);
            $success[] = $sql;
        } catch (PDOException $e) {
            // 字段已存在等错误可忽略
            $fail[] = $sql . ' -- ' . $e->getMessage();
        }
    }

    echo "<h3>升级完成</h3>";
    if ($success) {
        echo "<b>成功执行：</b><br><pre>" . implode("\n", $success) . "</pre>";
    }
    if ($fail) {
        echo "<b>跳过/失败：</b><br><pre>" . implode("\n", $fail) . "</pre>";
    }
    echo "<p>数据不会丢失，升级只会补充新字段/表。升级后请删除本文件！</p>";

} catch (Exception $e) {
    echo "<b>升级失败：</b>" . $e->getMessage();
} 