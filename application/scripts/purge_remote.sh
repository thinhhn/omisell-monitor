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

# Kiểm tra input
if [[ -z "$QUEUE_NAME" || ! "$QUEUE_NAME" =~ ^[a-zA-Z0-9_]+$ ]]; then
    echo "{\"error\": \"Invalid or missing queue name\"}"
    exit 1
fi

# Chạy lệnh
# Sử dụng dấu nháy đơn cho Python block để tránh Bash escape hell
ssh $SSH_KEY -o LogLevel=ERROR -o StrictHostKeyChecking=no -o ConnectTimeout=10 "$REMOTE_USER@$REMOTE_IP" "cd $CODE_DIR && sudo $VENV_CELERY -A omisell.celery purge -Q $QUEUE_NAME -f 2>&1" | python3 -c "
import sys
import json
import re

output = sys.stdin.read()
# Tìm số lượng trong chuỗi 'Purged X messages'
# Lưu ý: Lúc này Python chạy ở máy local để xử lý output trả về từ SSH
match = re.search(r'Purged\s+(\d+)', output)
purged_count = int(match.group(1)) if match else 0

success = any(keyword in output for keyword in ['Purged', 'No messages purged'])

result = {
    'queue': '$QUEUE_NAME',
    'killed_count': purged_count,
    'success': success
}
print(json.dumps(result))
"