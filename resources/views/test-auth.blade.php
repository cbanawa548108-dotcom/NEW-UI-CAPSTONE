<!DOCTYPE html>
<html>
<head>
    <title>Auth Test - FreshTrack</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Courier New', monospace; 
            padding: 30px; 
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            color: #0f0; 
            min-height: 100vh;
        }
        .container { max-width: 1200px; margin: 0 auto; }
        h1 { 
            color: #00ff88; 
            font-size: 32px; 
            margin-bottom: 30px; 
            text-shadow: 0 0 10px #00ff88;
            border-bottom: 3px solid #00ff88;
            padding-bottom: 15px;
        }
        h2 { 
            color: #00d4ff; 
            border-bottom: 2px solid #00d4ff; 
            padding: 15px 0 10px 0; 
            margin-top: 30px;
            font-size: 24px;
        }
        .success { color: #0f0; font-weight: bold; }
        .error { color: #f00; font-weight: bold; }
        .warning { color: #ff0; font-weight: bold; }
        .info { color: #00d4ff; }
        pre { 
            background: #000; 
            padding: 15px; 
            border: 2px solid #0f0; 
            border-radius: 8px;
            overflow-x: auto;
            margin: 15px 0;
            line-height: 1.6;
        }
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin: 20px 0;
            background: rgba(0, 0, 0, 0.5);
            border: 2px solid #0f0;
        }
        th, td { 
            padding: 12px; 
            text-align: left; 
            border: 1px solid #0f0;
        }
        th { 
            background: #1a4d2e; 
            color: #0f0; 
            font-weight: bold;
        }
        tr:nth-child(even) { background: rgba(0, 255, 0, 0.05); }
        .status-box {
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            border: 3px solid;
        }
        .status-box.success {
            background: rgba(0, 255, 0, 0.1);
            border-color: #0f0;
        }
        .status-box.error {
            background: rgba(255, 0, 0, 0.1);
            border-color: #f00;
        }
        .fix-steps {
            background: rgba(255, 255, 0, 0.1);
            border: 2px solid #ff0;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .fix-steps ol {
            margin-left: 20px;
            margin-top: 10px;
        }
        .fix-steps li {
            margin: 10px 0;
            color: #0f0;
        }
        code {
            background: #000;
            padding: 2px 8px;
            border-radius: 4px;
            color: #00ff88;
        }
        a { color: #00d4ff; text-decoration: none; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>
<div class="container">
    <h1>🔧 FreshTrack Authentication Test</h1>

    @if(isset($results['error']))
        <div class="status-box error">
            <p class="error">✗ Fatal Error: {{ $results['error'] }}</p>
        </div>
    @else
        <h2>1. Database Connection</h2>
        @if($results['db_connected'])
            <p class="success">✓ Connected to database: {{ config('database.connections.mysql.database') }}</p>
        @else
            <p class="error">✗ Database connection failed</p>
        @endif

        <h2>2. Users Table Check</h2>
        @if($results['users_found'] === 3)
            <p class="success">✓ All 3 demo accounts found</p>
        @else
            <p class="error">✗ Only {{ $results['users_found'] }} accounts found (expected 3)</p>
        @endif

        <h2>3. Password Hash Test</h2>
        @if(count($results['users']) > 0)
            <table>
                <thead>
                    <tr>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Hash (first 40 chars)</th>
                        <th>Password Works?</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($results['users'] as $user)
                        <tr>
                            <td>{{ $user['email'] }}</td>
                            <td>{{ $user['role'] }}</td>
                            <td>{{ $user['hash'] }}...</td>
                            <td class="{{ $user['works'] ? 'success' : 'error' }}">
                                {{ $user['works'] ? '✓ YES' : '✗ NO' }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p class="error">✗ No users found to test</p>
        @endif

        <h2>4. Manual Auth Test</h2>
        @if($results['test_user'])
            <p class="success">✓ User found via Eloquent</p>
            <pre>Email: {{ $results['test_user']['email'] }}
Name:  {{ $results['test_user']['name'] }}
Role:  {{ $results['test_user']['role'] }}</pre>

            @if($results['password_works'])
                <p class="success">✓ Password 'password' MATCHES the hash!</p>
                <p class="success">✓ Login SHOULD work now!</p>
            @else
                <p class="error">✗ Password 'password' DOES NOT MATCH the hash!</p>
                <p class="error">✗ This is why login is failing!</p>
            @endif
        @else
            <p class="error">✗ Test user not found!</p>
        @endif

        <h2>5. Laravel Auth Config</h2>
        <pre>Guard:    {{ config('auth.defaults.guard') }}
Provider: {{ config('auth.guards.web.provider') }}
Model:    {{ config('auth.providers.users.model') }}</pre>

        <h2>6. Session Config</h2>
        <pre>Driver:   {{ config('session.driver') }}
Lifetime: {{ config('session.lifetime') }} minutes</pre>

        <h2>7. Final Recommendation</h2>
        @if($results['all_passwords_work'])
            <div class="status-box success">
                <p class="success" style="font-size: 20px; margin-bottom: 10px;">✓✓✓ ALL PASSWORDS WORK! ✓✓✓</p>
                <p class="success">You should be able to login now!</p>
                <p style="margin-top: 15px;">Go to: <a href="/login">http://127.0.0.1:8000/login</a></p>
                <p style="margin-top: 10px;">Use credentials:</p>
                <pre>Email: owner@FreshTrack.ph
Password: password</pre>
            </div>
        @else
            <div class="status-box error">
                <p class="error" style="font-size: 20px; margin-bottom: 10px;">✗✗✗ PASSWORDS DON'T MATCH! ✗✗✗</p>
                <p class="warning">This is why you can't login. Run the fix below:</p>
            </div>

            <div class="fix-steps">
                <h3 class="warning">🔧 HOW TO FIX:</h3>
                <ol>
                    <li><strong>Double-click:</strong> <code>RUN-THIS-NOW.bat</code> in your project folder</li>
                    <li><strong>Or run in terminal:</strong> <code>php fix-login-now.php</code></li>
                    <li><strong>Or in phpMyAdmin:</strong> Run the SQL from <code>INSTANT-FIX.sql</code></li>
                    <li><strong>Then:</strong> Refresh this page to verify the fix worked</li>
                </ol>
            </div>
        @endif
    @endif

    <p style="margin-top: 40px; padding-top: 20px; border-top: 1px solid #0f0; color: #666;">
        <small>Test page: /test-auth | {{ now()->format('Y-m-d H:i:s') }}</small>
    </p>
</div>
</body>
</html>
