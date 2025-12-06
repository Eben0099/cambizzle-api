#!/bin/bash

# Script de test pour la réinitialisation de mot de passe
# Usage: bash test_password_reset.sh

set -e

BASE_URL="http://localhost:8000"
PHONE="+237677123456"
NEW_PASSWORD="newPassword123"

echo "==============================================="
echo "Test - Réinitialisation de Mot de Passe"
echo "==============================================="
echo ""

# Test 1: Forgot Password
echo "1. Testing forgot-password endpoint..."
FORGOT_RESPONSE=$(curl -s -X POST "$BASE_URL/api/auth/forgot-password" \
  -H "Content-Type: application/json" \
  -d "{\"phone\": \"$PHONE\"}")

echo "Response:"
echo "$FORGOT_RESPONSE" | jq '.'

# Extract token from response
RESET_TOKEN=$(echo "$FORGOT_RESPONSE" | jq -r '.data.token')
RESET_CODE=$(echo "$FORGOT_RESPONSE" | jq -r '.data.code')

if [ "$RESET_TOKEN" = "null" ] || [ -z "$RESET_TOKEN" ]; then
  echo "ERROR: No reset token in response!"
  exit 1
fi

echo "✓ Reset token received: ${RESET_TOKEN:0:20}..."
echo "✓ Reset code (dev only): $RESET_CODE"
echo ""

# Test 2: Reset Password with valid token
echo "2. Testing reset-password endpoint with valid token..."
RESET_RESPONSE=$(curl -s -X POST "$BASE_URL/api/auth/reset-password" \
  -H "Content-Type: application/json" \
  -d "{\"token\": \"$RESET_TOKEN\", \"password\": \"$NEW_PASSWORD\"}")

echo "Response:"
echo "$RESET_RESPONSE" | jq '.'

SUCCESS=$(echo "$RESET_RESPONSE" | jq -r '.success')
if [ "$SUCCESS" != "true" ]; then
  echo "ERROR: Password reset failed!"
  exit 1
fi

echo "✓ Password reset successful"
echo ""

# Test 3: Login with new password
echo "3. Testing login with new password..."
LOGIN_RESPONSE=$(curl -s -X POST "$BASE_URL/api/auth/login" \
  -H "Content-Type: application/json" \
  -d "{\"phone\": \"$PHONE\", \"password\": \"$NEW_PASSWORD\"}")

echo "Response:"
echo "$LOGIN_RESPONSE" | jq '.'

AUTH_TOKEN=$(echo "$LOGIN_RESPONSE" | jq -r '.data.token')
if [ "$AUTH_TOKEN" = "null" ] || [ -z "$AUTH_TOKEN" ]; then
  echo "ERROR: Login with new password failed!"
  exit 1
fi

echo "✓ Login successful with new password"
echo ""

# Test 4: Try using expired token
echo "4. Testing with invalid/expired token..."
INVALID_RESPONSE=$(curl -s -X POST "$BASE_URL/api/auth/reset-password" \
  -H "Content-Type: application/json" \
  -d "{\"token\": \"$RESET_TOKEN\", \"password\": \"anotherPassword\"}")

echo "Response:"
echo "$INVALID_RESPONSE" | jq '.'

ERROR=$(echo "$INVALID_RESPONSE" | jq -r '.code')
if [ "$ERROR" != "RESET_TOKEN_INVALID" ]; then
  echo "⚠ Warning: Token should be invalid after use"
else
  echo "✓ Token properly invalidated after use"
fi

echo ""

# Test 5: Test with missing phone
echo "5. Testing with missing phone parameter..."
MISSING_PHONE=$(curl -s -X POST "$BASE_URL/api/auth/forgot-password" \
  -H "Content-Type: application/json" \
  -d "{}")

echo "Response:"
echo "$MISSING_PHONE" | jq '.'

ERROR=$(echo "$MISSING_PHONE" | jq -r '.errors.phone // empty')
if [ -n "$ERROR" ]; then
  echo "✓ Validation error for missing phone"
fi

echo ""

# Test 6: Test with short password
echo "6. Testing with password too short..."
SHORT_PASSWORD=$(curl -s -X POST "$BASE_URL/api/auth/forgot-password" \
  -H "Content-Type: application/json" \
  -d "{\"phone\": \"$PHONE\"}")

RESET_TOKEN=$(echo "$SHORT_PASSWORD" | jq -r '.data.token')

SHORT_RESPONSE=$(curl -s -X POST "$BASE_URL/api/auth/reset-password" \
  -H "Content-Type: application/json" \
  -d "{\"token\": \"$RESET_TOKEN\", \"password\": \"123\"}")

echo "Response:"
echo "$SHORT_RESPONSE" | jq '.'

ERROR=$(echo "$SHORT_RESPONSE" | jq -r '.errors.password // empty')
if [ -n "$ERROR" ]; then
  echo "✓ Validation error for short password"
fi

echo ""
echo "==============================================="
echo "All tests completed!"
echo "==============================================="
