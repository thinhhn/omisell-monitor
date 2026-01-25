#!/bin/bash

# --- Cấu hình ---
# NOTE: Load từ environment variables để tăng cường bảo mật
REMOTE_IP="${REMOTE_CELERY_IP:-10.148.0.26}"
REMOTE_USER="${REMOTE_CELERY_USER:-thinhhn}"
KEY_PATH="${REMOTE_CELERY_KEY:-/home/thinhhn/.ssh/id_rsa}"
CODE_DIR="${REMOTE_CELERY_CODE_DIR:-/data/code/omisell-backend}"
VENV_CELERY="${REMOTE_CELERY_VENV_CELERY:-/data/venv/omisell3.11/bin/celery}"

# Nhận tên queue
QUEUE_NAME=$1

if [[ -z "$QUEUE_NAME" ]]; then
    echo "{\"error\": \"Missing queue name\"}"
    exit 1
fi

# Security: Validate queue name - chỉ cho phép alphanumeric và underscore
if [[ ! "$QUEUE_NAME" =~ ^[a-zA-Z0-9_]+$ ]]; then
    echo "{\"error\": \"Invalid queue name. Only alphanumeric and underscore allowed.\"}"
    exit 1
fi

# Security: Kiểm tra key file permissions
if [ -f "$KEY_PATH" ]; then
    KEY_PERMS=$(stat -c %a "$KEY_PATH" 2>/dev/null || stat -f %Lp "$KEY_PATH" 2>/dev/null)
    if [ "$KEY_PERMS" != "600" ] && [ "$KEY_PERMS" != "400" ]; then
        echo '{"error": "SSH key file has insecure permissions. Should be 600 or 400"}'
        exit 1
    fi
fi

# SSH và thực thi lệnh
# - Sử dụng python trên server đích để parse kết quả từ CLI của Celery cho chính xác
ssh -o LogLevel=ERROR -o StrictHostKeyChecking=no -i "$KEY_PATH" "$REMOTE_USER@$REMOTE_IP" "cd $CODE_DIR && sudo $VENV_CELERY -A omisell.celery purge -Q $QUEUE_NAME -f 2>&1 | python3 -c \"
import sys
import json
import re

output = sys.stdin.read()
# Tìm số lượng trong chuỗi 'Purged X messages'
match = re.search(r'Purged\\s+(\\d+)', output)
purged_count = int(match.group(1)) if match else 0

# Kiểm tra nếu có lỗi trong output hoặc lệnh không thành công
success = 'Purged' in output or 'No messages purged' in output

result = {
    'queue': '$QUEUE_NAME',
    'killed_count': purged_count,
    'success': success
}
print(json.dumps(result))
\"" 2>&1 | grep -E '^\{.*\}$'