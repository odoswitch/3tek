#!/bin/bash

echo "========================================="
echo "  Capture des Logs - 3TEK"
echo "========================================="
echo ""

cd /mnt/e/DEV_IA/3tek

echo "[1] État des conteneurs:"
echo "----------------------------------------"
docker compose ps
echo ""

echo "[2] Logs PHP (dernières 50 lignes):"
echo "----------------------------------------"
docker compose logs php --tail=50
echo ""

echo "[3] Logs Nginx (dernières 50 lignes):"
echo "----------------------------------------"
docker compose logs nginx --tail=50
echo ""

echo "[4] Logs Database (dernières 50 lignes):"
echo "----------------------------------------"
docker compose logs database --tail=50
echo ""

echo "[5] Logs PhpMyAdmin (dernières 50 lignes):"
echo "----------------------------------------"
docker compose logs phpmyadmin --tail=50
echo ""

echo "========================================="
echo "  Fin des logs"
echo "========================================="
