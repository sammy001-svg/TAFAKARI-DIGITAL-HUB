<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= h($pageTitle ?? 'Tafakari Digital Hub | Knowledge & Community Platform') ?></title>
  <meta name="description" content="<?= h($pageDesc ?? 'A centralized knowledge repository, media broadcasting center, and community engagement tool for Kenya, Ethiopia, and the DRC.') ?>">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;800;900&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          fontFamily: {
            outfit: ['Outfit', 'sans-serif'],
            inter:  ['Inter', 'sans-serif'],
          },
          colors: {
            primary:   '#9A1415',
            secondary: '#D99F51',
          },
          borderRadius: {
            '4xl': '2rem',
          }
        }
      }
    }
  </script>
  <style>
    :root {
      --primary:      #9A1415;
      --secondary:    #D99F51;
      --glass-bg:     rgba(154, 20, 21, 0.95);
      --glass-border: rgba(255, 255, 255, 0.15);
    }
    body { font-family: 'Inter', sans-serif; }
    .font-outfit { font-family: 'Outfit', sans-serif !important; }
    .glass {
      background: rgba(255,255,255,0.85);
      border: 1px solid rgba(255,255,255,0.3);
      backdrop-filter: blur(12px);
      -webkit-backdrop-filter: blur(12px);
    }
    .glass-dark {
      background: var(--glass-bg);
      border: 1px solid var(--glass-border);
      backdrop-filter: blur(12px);
      -webkit-backdrop-filter: blur(12px);
    }
    .btn-primary {
      display: inline-block;
      background: #9A1415;
      color: #fff;
      padding: 0.65rem 1.4rem;
      border-radius: 9999px;
      font-weight: 700;
      font-size: 0.875rem;
      text-decoration: none;
      border: none;
      cursor: pointer;
      transition: transform .15s, box-shadow .15s;
    }
    .btn-primary:hover  { transform: scale(1.04); box-shadow: 0 8px 24px rgba(154,20,21,.35); }
    .btn-primary:active { transform: scale(.97); }
    .btn-secondary {
      display: inline-block;
      background: #D99F51;
      color: #020617;
      padding: 0.65rem 1.4rem;
      border-radius: 9999px;
      font-weight: 700;
      font-size: 0.875rem;
      text-decoration: none;
      border: none;
      cursor: pointer;
      transition: transform .15s;
    }
    .btn-secondary:hover { transform: scale(1.04); }
    /* Gold button — for public content pages */
    .btn-gold {
      display: inline-flex;
      align-items: center;
      background: #D99F51;
      color: #0D0102;
      padding: 0.65rem 1.4rem;
      border-radius: 9999px;
      font-weight: 700;
      font-size: 0.875rem;
      text-decoration: none;
      border: none;
      cursor: pointer;
      transition: transform .15s, box-shadow .15s;
    }
    .btn-gold:hover  { transform: scale(1.04); box-shadow: 0 8px 24px rgba(217,159,81,.4); }
    .btn-gold:active { transform: scale(.97); }
    /* Cream/ivory outlined button */
    .btn-cream {
      display: inline-flex;
      align-items: center;
      background: #FBF5E6;
      color: #3B0708;
      padding: 0.65rem 1.4rem;
      border-radius: 9999px;
      font-weight: 700;
      font-size: 0.875rem;
      text-decoration: none;
      border: 1.5px solid #D99F51;
      cursor: pointer;
      transition: transform .15s, background .15s;
    }
    .btn-cream:hover { background: #F5E9C8; transform: scale(1.03); }
    .line-clamp-2 { display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden; }
    .line-clamp-3 { display:-webkit-box;-webkit-line-clamp:3;-webkit-box-orient:vertical;overflow:hidden; }
    .premium-gradient { background: #9A1415; }
  </style>
</head>
