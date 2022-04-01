@echo off

rem загрузка БД russia в MySQL

"C:/Program Files/MySQL/MySQL Server 5.5/bin/mysql" --host=127.0.0.1 --password= -u root --default-character-set=utf8 russia < russia.sql

echo =========================================

@pause