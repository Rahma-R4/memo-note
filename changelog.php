<?php
require_once __DIR__ . '/auth.php';

// Require authentication
$auth->requireAuth();

// Read changelog data from JSON file
$changelogFile = __DIR__ . '/changelog.json';
$changelogData = [];

if (file_exists($changelogFile)) {
    $jsonContent = file_get_contents($changelogFile);
    $changelogData = json_decode($jsonContent, true);
}

function getTypeIcon($type) {
    switch ($type) {
        case 'feature': return 'fas fa-star';
        case 'bugfix': return 'fas fa-bug';
        case 'security': return 'fas fa-shield-alt';
        case 'release': return 'fas fa-rocket';
        default: return 'fas fa-info-circle';
    }
}

function getTypeColor($type) {
    switch ($type) {
        case 'feature': return 'var(--success)';
        case 'bugfix': return 'var(--warning)';
        case 'security': return 'var(--danger)';
        case 'release': return 'var(--accent-primary)';
        default: return 'var(--text-secondary)';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Changelog - Memo Notepad</title>
    <link rel="icon" href="/assets/img/favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <div class="container">
        <header class="header">
            <div class="header-left">
                <h1><i class="fas fa-history"></i> Changelog</h1>
                <p>Version history and updates</p>
            </div>
            <div class="header-right">
                <a href="/" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i>
                    Back to App
                </a>
            </div>
        </header>

        <main class="main-content">
            <?php if (empty($changelogData['changelog'])): ?>
                <div class="empty-state">
                    <i class="fas fa-history"></i>
                    <h2>No changelog entries found</h2>
                    <p>Check back later for updates and version history.</p>
                </div>
            <?php else: ?>
                <div class="changelog-container">
                    <?php foreach ($changelogData['changelog'] as $entry): ?>
                        <div class="changelog-entry">
                            <div class="changelog-header">
                                <div class="version-info">
                                    <div class="version-badge" style="color: <?php echo getTypeColor($entry['type']); ?>">
                                        <i class="<?php echo getTypeIcon($entry['type']); ?>"></i>
                                        <span class="version">v<?php echo htmlspecialchars($entry['version']); ?></span>
                                    </div>
                                    <div class="release-date">
                                        <i class="fas fa-calendar-alt"></i>
                                        <?php echo date('F j, Y', strtotime($entry['date'])); ?>
                                    </div>
                                </div>
                                <div class="entry-type">
                                    <span class="type-badge <?php echo $entry['type']; ?>">
                                        <?php echo ucfirst($entry['type']); ?>
                                    </span>
                                </div>
                            </div>
                            
                            <div class="changelog-content">
                                <h3><?php echo htmlspecialchars($entry['title']); ?></h3>
                                
                                <ul class="changes-list">
                                    <?php foreach ($entry['changes'] as $change): ?>
                                        <li>
                                            <i class="fas fa-check"></i>
                                            <?php echo htmlspecialchars($change); ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <style>
        .changelog-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px 0;
        }

        .changelog-entry {
            background: var(--bg-secondary);
            border: 1px solid var(--border);
            border-radius: 12px;
            margin-bottom: 24px;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .changelog-entry:hover {
            border-color: var(--accent-primary);
            box-shadow: 0 8px 25px var(--shadow);
        }

        .changelog-header {
            background: var(--bg-primary);
            padding: 20px 24px;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
        }

        .version-info {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .version-badge {
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
            font-size: 1.1rem;
        }

        .version-badge i {
            font-size: 1.2rem;
        }

        .release-date {
            color: var(--text-secondary);
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .type-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .type-badge.feature {
            background: rgba(34, 197, 94, 0.1);
            color: var(--success);
            border: 1px solid rgba(34, 197, 94, 0.3);
        }

        .type-badge.bugfix {
            background: rgba(251, 191, 36, 0.1);
            color: var(--warning);
            border: 1px solid rgba(251, 191, 36, 0.3);
        }

        .type-badge.security {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger);
            border: 1px solid rgba(239, 68, 68, 0.3);
        }

        .type-badge.release {
            background: rgba(59, 130, 246, 0.1);
            color: var(--accent-primary);
            border: 1px solid rgba(59, 130, 246, 0.3);
        }

        .changelog-content {
            padding: 24px;
        }

        .changelog-content h3 {
            margin: 0 0 16px 0;
            color: var(--text-primary);
            font-size: 1.2rem;
            font-weight: 600;
        }

        .changes-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .changes-list li {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 8px 0;
            color: var(--text-primary);
            line-height: 1.5;
        }

        .changes-list li i {
            color: var(--success);
            margin-top: 2px;
            flex-shrink: 0;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--text-secondary);
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        .empty-state h2 {
            margin: 0 0 12px 0;
            color: var(--text-primary);
        }

        @media (max-width: 768px) {
            .changelog-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .version-info {
                flex-direction: column;
                align-items: flex-start;
                gap: 8px;
            }

            .changelog-content {
                padding: 20px;
            }
        }
    </style>
</body>
</html>
