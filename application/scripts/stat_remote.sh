#!/bin/bash

# --- Cấu hình ---
# NOTE: Các giá trị này nên được load từ config hoặc environment variables
# Để tăng cường bảo mật, đừng hardcode credentials trong script

# Load từ environment variables nếu có
REMOTE_IP="${REMOTE_CELERY_IP:-10.148.0.26}"
REMOTE_USER="${REMOTE_CELERY_USER:-thinhhn}"
KEY_PATH="${REMOTE_CELERY_KEY:-/home/thinhhn/.ssh/id_rsa}"
CODE_DIR="${REMOTE_CELERY_CODE_DIR:-/data/code/omisell-backend}"
VENV_PYTHON="${REMOTE_CELERY_VENV_PYTHON:-/data/venv/omisell3.11/bin/python}"

# Security: Kiểm tra key file permissions
if [ -f "$KEY_PATH" ]; then
    KEY_PERMS=$(stat -c %a "$KEY_PATH" 2>/dev/null || stat -f %Lp "$KEY_PATH" 2>/dev/null)
    if [ "$KEY_PERMS" != "600" ] && [ "$KEY_PERMS" != "400" ]; then
        echo '{"error": "SSH key file has insecure permissions. Should be 600 or 400"}' >&2
        exit 1
    fi
fi

ssh -o LogLevel=ERROR -o StrictHostKeyChecking=no -i $KEY_PATH $REMOTE_USER@$REMOTE_IP "cd $CODE_DIR && sudo $VENV_PYTHON -c \"
import json
from omisell.celery import app

# Khởi tạo inspect
ins = app.control.inspect()

# Lấy các task đang chờ xử lý trong queue (đã được worker nhận nhưng chưa chạy)
reserved = ins.reserved() or {}
# Lấy các task đang chạy
active = ins.active() or {}
# Lấy các task đã lên lịch
scheduled = ins.scheduled() or {}

stats = {}

def count_tasks(data):
    for worker, tasks in data.items():
        for task in tasks:
            # Lấy tên queue của task
            q_name = task.get('delivery_info', {}).get('routing_key', 'unknown')
            stats[q_name] = stats.get(q_name, 0) + 1

count_tasks(reserved)
count_tasks(active)
count_tasks(scheduled)

print(json.dumps(stats, indent=4))
\"" 2>&1 | grep -A 9999 '^{'