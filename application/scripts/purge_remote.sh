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

export QUEUE_NAME=$1

# 1. Kiểm tra đầu vào
if [[ -z "$QUEUE_NAME" || ! "$QUEUE_NAME" =~ ^[a-zA-Z0-9_]+$ ]]; then
    echo "{\"error\": \"Invalid or missing queue name\"}"
    exit 1
fi

# 2. Thực thi lệnh Purge và lưu kết quả
# Xuất kết quả ra biến môi trường để Python đọc trực tiếp
export RAW_OUTPUT=$(ssh $SSH_KEY -o LogLevel=ERROR -o StrictHostKeyChecking=no -o ConnectTimeout=10 "$REMOTE_USER@$REMOTE_IP" "cd $CODE_DIR && sudo $VENV_CELERY -A omisell.celery purge -Q $QUEUE_NAME -f 2>&1")

# 3. Sử dụng Python LOCAL với biến môi trường
python3 - << 'EOF'
import sys
import json
import re
import os

# Đọc dữ liệu từ biến môi trường của hệ thống
output = os.getenv("RAW_OUTPUT", "")
q_name = os.getenv("QUEUE_NAME", "unknown")

# Tìm số lượng trong chuỗi 'Purged X messages'
# Dùng raw string r'' để xử lý \s và \d mà không bị lỗi unicode
match = re.search(r'Purged\s+(\d+)', output)
purged_count = int(match.group(1)) if match else 0

# Kiểm tra nếu có dấu hiệu thành công trong output
success = any(keyword in output for keyword in ['Purged', 'No messages purged'])

result = {
    'queue': q_name,
    'killed_count': purged_count,
    'success': success
}

print(json.dumps(result))
EOF