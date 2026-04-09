from flask import Flask, render_template, request, redirect, session, url_for
from werkzeug.security import generate_password_hash, check_password_hash
import sqlite3

app = Flask(__name__)
app.secret_key = 'secret123'  # change this in real apps

DB = 'bank.db'

# --- DB setup ---
def get_db():
    con = sqlite3.connect(DB)
    con.row_factory = sqlite3.Row
    return con

def init_db():
    con = get_db()
    con.execute('''CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT UNIQUE,
        password TEXT,
        balance REAL DEFAULT 1000.0
    )''')
    con.execute('''CREATE TABLE IF NOT EXISTS transactions (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER,
        type TEXT,
        amount REAL,
        note TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )''')
    con.commit()
    
    # Create demo account if it doesn't exist
    try:
        hashed = generate_password_hash('demo123')
        con.execute("INSERT INTO users (username, password, balance) VALUES (?, ?, ?)", ('demo', hashed, 5000.0))
        con.commit()
    except sqlite3.IntegrityError:
        pass  # Demo account already exists
    
    con.close()

# --- Routes ---

@app.route('/')
def index():
    if 'user_id' in session:
        return redirect(url_for('dashboard'))
    return redirect(url_for('login'))


@app.route('/register', methods=['GET', 'POST'])
def register():
    error = None
    if request.method == 'POST':
        username = request.form['username'].strip()
        password = request.form['password']

        if len(password) < 6:
            error = "Password must be at least 6 characters"
        else:
            hashed = generate_password_hash(password)
            try:
                con = get_db()
                con.execute("INSERT INTO users (username, password) VALUES (?, ?)", (username, hashed))
                con.commit()
                con.close()
                return redirect(url_for('login'))
            except sqlite3.IntegrityError:
                error = "Username already taken"

    return render_template('register.html', error=error)


@app.route('/login', methods=['GET', 'POST'])
def login():
    error = None
    if request.method == 'POST':
        username = request.form['username'].strip()
        password = request.form['password']

        con = get_db()
        user = con.execute("SELECT * FROM users WHERE username = ?", (username,)).fetchone()
        con.close()

        if user and check_password_hash(user['password'], password):
            session['user_id'] = user['id']
            session['username'] = user['username']
            return redirect(url_for('dashboard'))
        else:
            error = "Invalid username or password"

    return render_template('login.html', error=error)


@app.route('/dashboard')
def dashboard():
    if 'user_id' not in session:
        return redirect(url_for('login'))

    con = get_db()
    user = con.execute("SELECT * FROM users WHERE id = ?", (session['user_id'],)).fetchone()
    txns = con.execute("SELECT * FROM transactions WHERE user_id = ? ORDER BY created_at DESC LIMIT 10",
                       (session['user_id'],)).fetchall()
    con.close()

    return render_template('dashboard.html', user=user, txns=txns)


@app.route('/deposit', methods=['POST'])
def deposit():
    if 'user_id' not in session:
        return redirect(url_for('login'))

    amount = float(request.form['amount'])
    if amount <= 0:
        return "Invalid amount", 400

    con = get_db()
    con.execute("UPDATE users SET balance = balance + ? WHERE id = ?", (amount, session['user_id']))
    con.execute("INSERT INTO transactions (user_id, type, amount, note) VALUES (?, 'deposit', ?, 'Deposit')",
                (session['user_id'], amount))
    con.commit()
    con.close()
    return redirect(url_for('dashboard'))


@app.route('/withdraw', methods=['POST'])
def withdraw():
    if 'user_id' not in session:
        return redirect(url_for('login'))

    amount = float(request.form['amount'])
    con = get_db()
    user = con.execute("SELECT * FROM users WHERE id = ?", (session['user_id'],)).fetchone()

    if amount <= 0 or amount > user['balance']:
        con.close()
        return "Insufficient balance or invalid amount", 400

    con.execute("UPDATE users SET balance = balance - ? WHERE id = ?", (amount, session['user_id']))
    con.execute("INSERT INTO transactions (user_id, type, amount, note) VALUES (?, 'withdraw', ?, 'Withdrawal')",
                (session['user_id'], amount))
    con.commit()
    con.close()
    return redirect(url_for('dashboard'))


@app.route('/transfer', methods=['POST'])
def transfer():
    if 'user_id' not in session:
        return redirect(url_for('login'))

    to_user = request.form['to_user'].strip()
    amount = float(request.form['amount'])

    con = get_db()
    sender = con.execute("SELECT * FROM users WHERE id = ?", (session['user_id'],)).fetchone()
    receiver = con.execute("SELECT * FROM users WHERE username = ?", (to_user,)).fetchone()

    if not receiver:
        con.close()
        return "Recipient not found", 404
    if receiver['id'] == session['user_id']:
        con.close()
        return "Cannot transfer to yourself", 400
    if amount <= 0 or amount > sender['balance']:
        con.close()
        return "Insufficient balance or invalid amount", 400

    con.execute("UPDATE users SET balance = balance - ? WHERE id = ?", (amount, session['user_id']))
    con.execute("UPDATE users SET balance = balance + ? WHERE id = ?", (amount, receiver['id']))
    con.execute("INSERT INTO transactions (user_id, type, amount, note) VALUES (?, 'transfer_out', ?, ?)",
                (session['user_id'], amount, f"Sent to {to_user}"))
    con.execute("INSERT INTO transactions (user_id, type, amount, note) VALUES (?, 'transfer_in', ?, ?)",
                (receiver['id'], amount, f"Received from {session['username']}"))
    con.commit()
    con.close()
    return redirect(url_for('dashboard'))


@app.route('/logout')
def logout():
    session.clear()
    return redirect(url_for('login'))


if __name__ == '__main__':
    init_db()
    app.run(debug=True)
