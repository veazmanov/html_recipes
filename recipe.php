<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php $name = basename($_GET['name'] ?? ''); ?>
    <title><?php echo $name ? htmlspecialchars(ucwords(str_replace('-', ' ', $name))) . ' | Recipes' : 'Recipe'; ?></title>
    <?php $css_version = @filemtime(__DIR__ . '/style.css') ?: time(); ?>
    <link rel="stylesheet" href="style.css?v=<?php echo $css_version; ?>">
</head>
<body>
    <article class="container">
        <?php include 'masthead.php'; ?>
        <div class="recipe-tools">
            <a href="index.php">← All recipes</a>
            <button type="button" class="print-button" onclick="window.print()">Print</button>
        </div>
        <?php
        $path = "html/$name.html";
        if (!empty($name) && file_exists($path)) {
            // Images now come from this recipe's markdown frontmatter,
            // baked into manifest.json at build time (see ./build).
            $images = [];
            if (file_exists('manifest.json')) {
                $manifest = json_decode(file_get_contents('manifest.json'), true) ?: [];
                foreach ($manifest as $r) {
                    if ($r['slug'] === $name) {
                        $images = $r['images'] ?? [];
                        break;
                    }
                }
            }

            // 1. Recipe Content
            include($path);

            // 2. Image Carousel — full URLs listed directly in frontmatter
            if (count($images) > 0) {
                echo '<div class="carousel-container">
                        <div class="carousel-track">';

                foreach ($images as $image) {
                    $src = htmlspecialchars($image, ENT_QUOTES);
                    echo "<img src='{$src}' class='carousel-img' loading='lazy' onerror='this.remove()'>";
                }

                echo '  </div>
                        <div class="carousel-hint" id="carousel-hint">Swipe for more photos ↔</div>
                      </div>';
            }

            // 3. Small Script to hide hint if only one image exists,
            //    AND persist checklist state to localStorage per recipe.
            echo "
            <script>
                window.onload = function() {
                    const images = document.querySelectorAll('.carousel-img');
                    const hint = document.getElementById('carousel-hint');
                    if (hint && images.length <= 1) {
                        hint.style.display = 'none';
                    }
                };
            </script>";

            echo "
            <script>
            (function() {
                const recipeKey = 'checklist:' + " . json_encode($name) . ";
                const boxes = document.querySelectorAll('.container input[type=\"checkbox\"]');
                let saved = {};
                try { saved = JSON.parse(localStorage.getItem(recipeKey)) || {}; } catch (e) {}
                boxes.forEach((box) => {
                    const label = box.closest('label');
                    const text = label ? label.textContent.trim() : box.outerHTML;
                    if (saved[text] !== undefined) box.checked = saved[text];
                    box.addEventListener('change', () => {
                        saved[text] = box.checked;
                        localStorage.setItem(recipeKey, JSON.stringify(saved));
                    });
                });
            })();
            </script>";

            // 4. Numbered instructions: clicking step N marks steps 1..N done.
            //    Clicking the last done step again steps back by one.
            echo "
            <script>
            (function() {
                const stepsKey = 'steps:' + " . json_encode($name) . ";
                let saved = {};
                try { saved = JSON.parse(localStorage.getItem(stepsKey)) || {}; } catch (e) {}
                document.querySelectorAll('.container ol').forEach((list, listIndex) => {
                    const steps = Array.from(list.children).filter((el) => el.tagName === 'LI');
                    let done = saved[listIndex] || 0;
                    const render = () => steps.forEach((step, i) => step.classList.toggle('done', i < done));
                    const select = (i) => {
                        done = (done === i + 1) ? i : i + 1;
                        saved[listIndex] = done;
                        try { localStorage.setItem(stepsKey, JSON.stringify(saved)); } catch (e) {}
                        render();
                    };
                    steps.forEach((step, i) => {
                        step.classList.add('step');
                        step.tabIndex = 0;
                        step.addEventListener('click', () => select(i));
                        step.addEventListener('keydown', (e) => {
                            if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); select(i); }
                        });
                    });
                    render();
                });
            })();
            </script>";

        } else {
            echo "<h1>Recipe not found</h1>";
        }
        ?>
    </article>
    <?php
    $edit_name = $name; // From your existing $name variable
    include 'footer.php';
    ?>
</body>
</html>