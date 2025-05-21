<?php
include 'config.php';
session_start();

$slug = $_GET['slug'] ?? '';
if (empty($slug)) die("Slug is missing");

// Handle image uploads
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['images'])) {
    $uploadDir = 'uploads/';
    if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);

    $uploaded = [];
    $baseUrl = 'https://xcelmarketing.in/blog-admin/'; 

    foreach ($_FILES['images']['tmp_name'] as $index => $tmpName) {
        $name = basename($_FILES['images']['name'][$index]);
        $target = $uploadDir . time() . '_' . $name;

        $imageInfo = getimagesize($tmpName);
        $mime = $imageInfo['mime'];

        // Load original image
        if ($mime === 'image/jpeg') {
            $srcImage = imagecreatefromjpeg($tmpName);
        } elseif ($mime === 'image/png') {
            $srcImage = imagecreatefrompng($tmpName);
        } elseif ($mime === 'image/gif') {
            $srcImage = imagecreatefromgif($tmpName);
        } else {
            continue; // Skip unsupported types
        }

        // Create resized image (525x350)
        $newWidth = 525;
        $newHeight = 350;
        $newImage = imagecreatetruecolor($newWidth, $newHeight);

        // Resize
        imagecopyresampled($newImage, $srcImage, 0, 0, 0, 0, $newWidth, $newHeight, imagesx($srcImage), imagesy($srcImage));

        // Save resized image
        if ($mime === 'image/jpeg') {
            imagejpeg($newImage, $target, 90);
        } elseif ($mime === 'image/png') {
            imagepng($newImage, $target);
        } elseif ($mime === 'image/gif') {
            imagegif($newImage, $target);
        }

        // Free memory
        imagedestroy($srcImage);
        imagedestroy($newImage);

        // Store the full URL instead of just the path
        $uploaded[] = $baseUrl . $target;
    }

    header('Content-Type: application/json');
    echo json_encode($uploaded);
    exit;
}

// Get post data
$stmt = $conn->prepare("SELECT * FROM xcel_posts WHERE slug = ?");
$stmt->bind_param("s", $slug);
$stmt->execute();
$result = $stmt->get_result();
$post = $result->fetch_assoc();
if (!$post) die("Post not found");

// Update post
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_FILES['images'])) {
    $sub_content = $_POST['sub_content'];
    $table_of_content = $_POST['table_of_content'];
    $stmt = $conn->prepare("UPDATE xcel_posts SET sub_content = ?, table_of_content = ? WHERE slug = ?");
    $stmt->bind_param("sss", $sub_content, $table_of_content, $slug);
    $stmt->execute();
    $_SESSION['success'] = "Post updated.";
    header("Location: view-add-all-post.php?slug=$slug");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Edit Post - <?= htmlspecialchars($post['title']) ?></title>
    <link
        href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&family=Playfair+Display:wght@400;700&display=swap"
        rel="stylesheet">
    <link rel="st
    <link rel="stylesheet" href="./css/style.css">
    
<?php include 'header.php'; ?>
<?php include 'sidebar.php'; ?>
    <style>
      
   

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

  

        h2 {
            font-family: 'Playfair Display', serif;
            color: var(--secondary-color);
            margin-bottom: 20px;
            font-size: 28px;
            border-bottom: 2px solid var(--accent-color);
            padding-bottom: 10px;
        }

        .alert {
            padding: 12px 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .alert-success {
            background-color: #d4edda;
            color: var(--success-color);
            border: 1px solid #c3e6cb;
        }

        .editor-container {
            background-color: white;
            border-radius: 8px;
            box-shadow: var(--shadow);
            overflow: hidden;
            margin-bottom: 30px;
        }

        .toolbar {
            background-color: #f8f9fa;
            padding: 10px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
        }

        .toolbar button,
        .toolbar select {
            background-color: white;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            padding: 8px 12px;
            cursor: pointer;
            font-size: 14px;
            color: var(--dark-color);
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .toolbar button:hover,
        .toolbar select:hover {
            background-color: #e9ecef;
            border-color: #adb5bd;
        }

        .toolbar button i {
            font-size: 14px;
        }

        #editor {
            min-height: 400px;
            padding: 20px;
            outline: none;
            line-height: 1.8;
            font-size: 16px;
        }

        #editor:focus {
            background-color: #fff;
        }

        #editor img {
            max-width: 100%;
            height: auto;
            display: block;
            margin: 10px auto;
            border-radius: 4px;
        }

        .file-upload {
            margin: 20px 0;
            padding: 20px;
            background-color: white;
            border-radius: 8px;
            box-shadow: var(--shadow);
        }

        .file-upload label {
            display: block;
            margin-bottom: 10px;
            font-weight: 500;
            color: var(--dark-color);
        }

        .file-upload input[type="file"] {
            display: none;
        }

        .custom-file-upload {
            display: inline-block;
            padding: 10px 15px;
            background-color: var(--primary-color);
            color: white;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .custom-file-upload:hover {
            background-color: var(--secondary-color);
        }

        .submit-btn {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 12px 25px;
            font-size: 16px;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.3s;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .submit-btn:hover {
              background-color: var(--primary-color);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .submit-btn i {
            font-size: 14px;
        }

        footer {
            text-align: center;
            margin-top: 40px;
            padding: 20px;
            color: #6c757d;
            font-size: 14px;
        }

        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }

            .toolbar {
                gap: 3px;
            }

            .toolbar button,
            .toolbar select {
                padding: 6px 8px;
                font-size: 12px;
            }
        }

        /* Make Table of Contents sticky and fixed */
        #tableOfContentsWrapper {
            position: fixed;
            top: 20px;
            left: 15px;
            z-index: 10;
            background-color: white;
            padding: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            width: 1420px;
            /* max-height: 90v; */
            height: fit-content;
            overflow-y: auto;
        }

        #tableOfContents {
            list-style: none;
            padding: 0;
        }

        #tableOfContents li {
            margin-bottom: 10px;
        }

        #tableOfContents a {
            color: #007bff;
            text-decoration: none;
            font-size: 14px;
        }

        #tableOfContents a:hover {
            text-decoration: underline;
        }
    </style>
</head>

<body>


    <div class="container" style="display: flex; gap: 20px; margin-bottom:80px;">
    <!-- Table of Content (left side) -->
    <div id="tableOfContentsWrapper" class="col-5" style="position: sticky; top: 90px;">
        <h3>Table of Contents</h3>
        <ul id="tableOfContents"></ul>
    </div>
        <!-- Editor (right side) -->
        <div class="col-9">
            <h2>Edit: <?= htmlspecialchars($post['title']) ?></h2>

            <?php if (!empty($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <?= $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" onsubmit="beforeSubmit()">
                <div class="editor-container">
                    <div class="toolbar">
                        <select onchange="execCmd('formatBlock', this.value)">
                            <option value="p">Paragraph</option>
                            <option value="h1">Heading 1</option>
                            <option value="h2">Heading 2</option>
                            <option value="h3">Heading 3</option>
                            <option value="h4">Heading 4</option>
                            <option value="h4">Heading 5</option>
                            <option value="h4">Heading 6</option>
                        </select>
                        
                        <button type="button" onclick="execCmd('bold')"><i class="fas fa-bold"></i> Bold</button>
                        <button type="button" onclick="execCmd('italic')"><i class="fas fa-italic"></i> Italic</button>
                        <button type="button" onclick="execCmd('underline')"><i class="fas fa-underline"></i>
                            Underline</button>
                        <button type="button" onclick="execCmd('insertUnorderedList')"><i class="fas fa-list-ul"></i>
                            List</button>
                        <button type="button" onclick="execCmd('insertOrderedList')"><i class="fas fa-list-ol"></i>
                            Ordered</button>
                        <button type="button" onclick="insertLink()"><i class="fas fa-link"></i> Link</button>
                    </div>
                    <br>

                    <input type="file" id="imageUpload" multiple accept="image/*"><br><br>

                    <div id="editor" contenteditable="true" oninput="generateTOC()"><?= $post['sub_content'] ?></div>
                    <textarea name="sub_content" id="sub_content" style="display:none;"></textarea>
                    <input type="hidden" name="table_of_content" id="table_of_content_input">

                </div>

                <button type="submit" class="submit-btn">
                    <i class="fas fa-save"></i> Save Post
                </button>
            </form>
        </div>

    </div>


    <?php include('./footer.php'); ?>
    <script>
        function execCmd(cmd, val = null) {
            document.execCommand(cmd, false, val);
        }

        function insertLink() {
            const url = prompt("Enter link URL:");
            if (url) document.execCommand("createLink", false, url);
        }

        function beforeSubmit() {
            var editorContent = document.getElementById('editor').innerHTML;
            var tocContent = document.getElementById('tableOfContents').innerHTML;
            var subContentInput = document.createElement('input');
            subContentInput.type = 'hidden';
            subContentInput.name = 'sub_content';
            subContentInput.value = editorContent;
            document.querySelector('form').appendChild(subContentInput);
            // Now set table_of_content
            document.getElementById('table_of_content_input').value = tocContent;
        }
        // Dynamic Table of Contents
        function generateTOC() {
            const editor = document.getElementById('editor');
            const toc = document.getElementById('tableOfContents');
            toc.innerHTML = '';
            const headings = editor.querySelectorAll('h1, h2, h3, h4');
            headings.forEach((heading, index) => {
                // Give headings an ID if they don't have one
                if (!heading.id) {
                    heading.id = 'heading-' + index;
                }
                const li = document.createElement('li');
                const a = document.createElement('a');
                a.href = '#' + heading.id;
                a.textContent = heading.textContent;
                a.style.display = 'block';
                a.style.marginBottom = '10px';
                a.style.color = 'black';
                a.style.textDecoration = 'none';
                a.style.fontSize = '14px';
                li.appendChild(a);
                toc.appendChild(li);
            });
        }





 // Function to update the dropdown based on current selection
    // Function to update the dropdown based on current selection
    function updateFormatDropdown() {
        const selection = window.getSelection();
        if (!selection || selection.rangeCount === 0) return;
        
        const range = selection.getRangeAt(0);
        let blockElement = range.commonAncestorContainer;
        
        // Walk up the DOM tree to find the block-level element
        while (blockElement && blockElement.nodeType === Node.TEXT_NODE) {
            blockElement = blockElement.parentNode;
        }
        
        // Continue walking up until we find a block element
        while (blockElement && !isBlockElement(blockElement)) {
            blockElement = blockElement.parentNode;
        }
        
        if (blockElement) {
            const tagName = blockElement.nodeName.toLowerCase();
            const dropdown = document.querySelector('.toolbar select');
            
            // Update dropdown to match current block element
            if (['h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'p'].includes(tagName)) {
                dropdown.value = tagName;
            } else {
                // For other block elements (div, etc.), check if they contain a heading
                const heading = blockElement.querySelector('h1, h2, h3, h4, h5, h6');
                if (heading) {
                    dropdown.value            .then(images => {
                    images
                    dropdown.value = heading.nodeName.toLowerCase();
                } else {
                    dropdown.value = 'p'; // Default to paragraph
                }
            }
        }
    }

    // Helper function to check if an element is block-level
    function isBlockElement(element) {
        if (!element || !element.nodeType === Node.ELEMENT_NODE) return false;
        const blockElements = ['DIV', 'P', 'H1', 'H2', 'H3', 'H4', 'H5', 'H6', 'LI', 'BLOCKQUOTE'];
        return blockElements.includes(element.nodeName);
    }

    // Add event listeners to track cursor movement
    document.getElementById('editor').addEventListener('click', function() {
        setTimeout(updateFormatDropdown, 10); // Small delay to ensure selection is updated
    });
    
    document.getElementById('editor').addEventListener('keyup', function() {
        setTimeout(updateFormatDropdown, 10);
    });
    
    // Also track arrow key movements
    document.getElementById('editor').addEventListener('keydown', function(e) {
        if ([37, 38, 39, 40].includes(e.keyCode)) { // Arrow keys
            setTimeout(updateFormatDropdown, 10);
        }
    });

    // Initialize on load
    window.addEventListener('load', function() {
        setTimeout(updateFormatDropdown, 100); // Delay to ensure editor is loaded
    });
    

    // Also call it on initial load
    window.addEventListener('load', updateFormatDropdown);

        // Re-generate TOC when page loads
        window.addEventListener('load', generateTOC);
        // Upload image and insert into editor
// Upload image and insert into editor at cursor position
document.getElementById('imageUpload').addEventListener('change', function() {
    const files = this.files;
    if (files.length === 0) return;
    
    const editor = document.getElementById('editor');
    const selection = window.getSelection();
    let range;
    
    // If there's a selection, remember the range
    if (selection.rangeCount > 0) {
        range = selection.getRangeAt(0);
    }
    
    const formData = new FormData();
    for (let i = 0; i < files.length; i++) {
        formData.append('images[]', files[i]);
    }
    
    fetch(window.location.href, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(images => {
            images.forEach(src => {
                const img = document.createElement('img');
                img.src = src;
                
                // If we had a selection range, insert at that position
                if (range) {
                    range.deleteContents(); // Remove any selected content
                    range.insertNode(img);
                    
                    // Move cursor after the inserted image
                    const newRange = document.createRange();
                    newRange.setStartAfter(img);
                    newRange.collapse(true);
                    selection.removeAllRanges();
                    selection.addRange(newRange);
                } else {
                    // Otherwise append to the end
                    editor.appendChild(img);
                }
            });
            generateTOC();
        })
        .catch(error => console.error('Error uploading images:', error));
});
    </script>

  

</body>

</html>       const img = document.createElement('img');
                        img.src = src;
                        document.getElementById('editor').appendChild(img);
                    });
                    generateTOC();
                })
                .catch(error => console.error('Error uploading images:', error));
        });
    </script>

  

</body>

</html>