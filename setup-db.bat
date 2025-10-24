@echo off
docker exec 3tek-database-1 mysql -u root -pngamba123 -e "CREATE DATABASE IF NOT EXISTS `3tek`;"
docker exec 3tek-database-1 mysql -u root -pngamba123 -e "CREATE USER IF NOT EXISTS 'app'@'%%' IDENTIFIED BY 'ngamba123';"
docker exec 3tek-database-1 mysql -u root -pngamba123 -e "GRANT ALL PRIVILEGES ON `3tek`.* TO 'app'@'%%';"
docker exec 3tek-database-1 mysql -u root -pngamba123 -e "FLUSH PRIVILEGES;"
echo Database 3tek created and user app configured successfully!
