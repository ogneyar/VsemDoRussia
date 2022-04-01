@echo off

rem скачивание dump'a БД russia

"C:/Program Files/MySQL/MySQL Server 5.5/bin/mysqldump" --host=127.0.0.1 --password= -u root --disable-keys --add-drop-table --default-character-set=utf8 --result-file=russia.sql russia

echo =========================================

@pause