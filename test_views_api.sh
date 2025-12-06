#!/usr/bin/env bash
# Test Script for Views Tracking API
# Usage: bash test_views_api.sh

BASE_URL="http://localhost:8080/api"

echo "🔍 === TEST 1: Obtenir les détails d'une annonce (incrémente les vues) ==="
echo "GET $BASE_URL/ads/123"
curl -X GET "$BASE_URL/ads/123" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -w "\n\nStatus: %{http_code}\n\n"

echo ""
echo "🔍 === TEST 2: Lister les annonces triées par vues (décroissant) ==="
echo "GET $BASE_URL/ads/?sort_by=view_count&sort_order=DESC&per_page=10"
curl -X GET "$BASE_URL/ads/?sort_by=view_count&sort_order=DESC&per_page=10" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -w "\n\nStatus: %{http_code}\n\n"

echo ""
echo "🔍 === TEST 3: Annonces d'un utilisateur (avec view_count) ==="
echo "GET $BASE_URL/ads/user/42"
curl -X GET "$BASE_URL/ads/user/42" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -w "\n\nStatus: %{http_code}\n\n"

echo ""
echo "📊 === TEST 4: NOUVEAU - Statistiques de vues d'un utilisateur ==="
echo "GET $BASE_URL/ads/user/42/views-stats"
curl -X GET "$BASE_URL/ads/user/42/views-stats" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -w "\n\nStatus: %{http_code}\n\n"

echo ""
echo "🔍 === TEST 5: Annonces par catégorie (avec view_count) ==="
echo "GET $BASE_URL/ads/category/5?sort_by=view_count&sort_order=DESC"
curl -X GET "$BASE_URL/ads/category/5?sort_by=view_count&sort_order=DESC" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -w "\n\nStatus: %{http_code}\n\n"

echo ""
echo "🔍 === TEST 6: Annonces par sous-catégorie (avec view_count) ==="
echo "GET $BASE_URL/ads/subcategory/2?sort_by=view_count"
curl -X GET "$BASE_URL/ads/subcategory/2?sort_by=view_count" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -w "\n\nStatus: %{http_code}\n\n"

echo ""
echo "✅ Tests terminés!"
