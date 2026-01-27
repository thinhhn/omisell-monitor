#!/bin/bash

# --- Cấu hình ---
REMOTE_IP="${REMOTE_CELERY_IP:-10.148.0.26}"
REMOTE_USER="${REMOTE_CELERY_USER:-thinhhn}"
CODE_DIR="${REMOTE_CELERY_CODE_DIR:-/data/code/omisell-backend}"
VENV_CELERY="${REMOTE_CELERY_VENV_CELERY:-/data/venv/omisell3.11/bin/celery}"

# SSH key logic
SSH_KEY=""
[ -f ~/.ssh/thinhhn_id_rsa ] && SSH_KEY="-i ~/.ssh/thinhhn_id_rsa"
[ -f ~/.ssh/id_rsa ] && [ -z "$SSH_KEY" ] && SSH_KEY="-i ~/.ssh/id_rsa"

QUEUE_NAME=$1

# 1. Kiểm tra input để bảo mật
if [[ -z "$QUEUE_NAME" || ! "$QUEUE_NAME" =~ ^[a-zA-Z0-9_]+$ ]]; then
    echo "{\"error\": \"Invalid or missing queue name\"}"
    exit 1
fi

# 2. Thực thi lệnh Purge và nhận output về Local
# Chúng ta dùng '2>&1' để bắt cả lỗi và thông báo thành công
RAW_OUTPUT=$(ssh $SSH_KEY -o LogLevel=ERROR -o StrictHostKeyChecking=no -o ConnectTimeout=10 "$REMOTE_USER@$REMOTE_IP" "cd $CODE_DIR && sudo $VENV_CELERY -A omisell.celery purge -Q $QUEUE_NAME -f 2>&1")

# 3. Sử dụng Python tại máy Local để parse dữ liệu (Tránh lỗi encoding trên SSH)
echo "$RAW_OUTPUT" | python3 -c "
import sys
import json
import re

output = sys.stdin.read()

# Parse số lượng từ chuỗi 'Purged X messages'
match = re.search(r'Purged\s+(\d+)', output)
purged_count = int(match.group(1)) if match else 0

# Kiểm tra trạng thái thành công
success = any(keyword in output for keyword in ['Purged', 'No messages purged'])

print(json.dumps({
    'queue': '$QUEUE_NAME',
    'killed_count': purged_count,
    'success': success
}))
"