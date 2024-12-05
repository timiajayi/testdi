<?php
require_once 'auth.php';
?>
<!DOCTYPE html>
<html>
<head>
    <title>ID Card Generator</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/croppie/2.6.5/croppie.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        

        .company-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .company-logo {
            max-width: 200px;
            max-height: 100px;
        }

        .form-container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #333;
        }

        input[type="text"],
        input[type="file"],
        select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }

        button {
            background: #007bff;
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            transition: background 0.3s;
        }

        button:hover {
            background: #0056b3;
        }

        .preview-container {
            display: flex;
            gap: 30px;
            margin-top: 30px;
            justify-content: center;
        }

        .preview-box {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            flex: 1;
            max-width: 400px;
        }

        .preview-image {
            max-width: 100%;
            height: auto;
            border-radius: 5px;
        }

        .loader {
            display: none;
            text-align: center;
            margin: 20px 0;
        }

        .loader-spinner {
            width: 50px;
            height: 50px;
            border: 5px solid #f3f3f3;
            border-radius: 50%;
            border-top: 5px solid #007bff;
            animation: spin 1s linear infinite;
            margin: 0 auto;
        }

        .download-btn {
            display: inline-block;
            padding: 10px 20px;
            margin: 10px;
            background: #28a745;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background 0.3s;
        }

        .download-btn:hover {
            background: #218838;
        }

        #cropper-container {
            width: 400px;
            height: 400px;
            margin: 0 auto;
        }

        .cr-viewport {
            border-radius: 50%;
        }

        #crop-button {
            display: block;
            margin: 10px auto;
            padding: 10px 20px;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        #crop-button:hover {
            background: #0056b3;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .secondary-btn {
            background: #6c757d;
            margin-left: 10px;
        }

        .secondary-btn:hover {
            background: #5a6268;
        }

    </style>
</head>
<body>
    <div class="company-header">
        <img src="./templates/logo.png" alt="Company Logo" class="company-logo">
        <h1>ID Card Generator</h1>
    </div>

    <div class="form-container">
        <form id="idCardForm">
            <div class="form-group">
                <label>ID Card Type</label>
                <select name="template_type" required>
                    <option value="delog">Delog ID Card</option>
                    <option value="gecs">GECS ID Card</option>
                    <option value="nysc">NYSC Intern ID Card</option>
                    <option value="staff">Staff ID Card</option>
                </select>
            </div>

            <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="full_name" placeholder="Enter full name" required>
            </div>

            <div class="form-group">
                <label>ID Number (Optional)</label>
                <input type="text" name="id_number" placeholder="Enter ID number">
            </div>

            <div class="form-group">
                <label>Department (Optional)</label>
                <input type="text" name="department" placeholder="Enter department">
            </div>

            <div class="form-group">
                <label>Profile Photo</label>
                <input type="file" name="user_image" id="image-input" accept="image/*" required>
                <div id="image-cropper" style="display:none; margin: 20px 0;">
                    <div id="cropper-container"></div>
                    <button type="button" id="crop-button">Crop and Set Image</button>
                </div>
            </div>
            

            <div class="form-group">
                <label>QR Code Image (Optional)</label>
                <input type="file" name="qr_code" accept="image/*">
            </div>
            <div class="form-group">
                <label>Generate QR Code Content (Optional)</label>
                <textarea id="qrContent" placeholder="Enter content for QR code generation"></textarea>
                <button type="button" onclick="generateQR()" class="secondary-btn" style="margin-top: 10px;">Generate QR Code</button>
                <div id="qrPreview" style="margin-top: 10px; text-align: center;"></div>
            </div>
            
            

            <button type="submit">Generate ID Card</button>
        </form>
        <div class="nav-links">
            <a href="gallery.php" class="nav-link">View ID Card Gallery</a>
        </div>
        
    </div>

    <div class="loader" id="loader">
        <div class="loader-spinner"></div>
        <p>Generating ID Card...</p>
    </div>

    <div class="preview-container">
        <div class="preview-box">
            <h3>Front Preview</h3>
            <img id="frontPreview" class="preview-image">
        </div>
        <div class="preview-box">
            <h3>Back Preview</h3>
            <img id="backPreview" class="preview-image">
        </div>
    </div>

    <div id="downloadButtons" style="text-align: center; margin-top: 20px;"></div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/croppie/2.6.5/croppie.min.js"></script>
    <script>
        let croppie;
        document.getElementById('image-input').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(event) {
                    document.getElementById('image-cropper').style.display = 'block';
                    
                    if (croppie) {
                        croppie.destroy();
                    }
                    
                    croppie = new Croppie(document.getElementById('cropper-container'), {
                        viewport: { width: 307, height: 307, type: 'circle' },
                        boundary: { width: 400, height: 400 },
                        enableZoom: true,
                        enableOrientation: true
                    });
                    
                    croppie.bind({
                        url: event.target.result
                    });
                }
                reader.readAsDataURL(file);
            }
        });

        document.getElementById('crop-button').addEventListener('click', function() {
    croppie.result({
        type: 'blob',
        size: { width: 307, height: 307 },
        format: 'jpeg',
        quality: 1,
        circle: true
    }).then(function(blob) {
        const croppedFile = new File([blob], "profile.jpg", { type: "image/jpeg" });
        
        // Update the original file input
        const dataTransfer = new DataTransfer();
        dataTransfer.items.add(croppedFile);
        document.getElementById('image-input').files = dataTransfer.files;
        
        // Show preview
        const previewUrl = URL.createObjectURL(blob);
        const preview = document.createElement('img');
        preview.src = previewUrl;
        preview.style.width = '150px';
        preview.style.height = '150px';
        preview.style.borderRadius = '50%';
        preview.style.objectFit = 'cover';
        preview.style.marginTop = '10px';
        
        const container = document.getElementById('cropper-container');
        container.innerHTML = '';
        container.appendChild(preview);
        
        document.getElementById('crop-button').style.display = 'none';
    });
});



document.getElementById('idCardForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    document.getElementById('loader').style.display = 'block';
    
    const formData = new FormData(this);
    
    try {
        const response = await fetch('generate.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            const basePath = window.location.origin; // This will get the base URL like "http://10.100.11.151"
            const frontImagePath = basePath + '/' + data.front_image;
            const backImagePath = basePath + '/' + data.back_image;

            document.getElementById('frontPreview').src = frontImagePath;
            document.getElementById('backPreview').src = backImagePath;
            
            document.getElementById('downloadButtons').innerHTML = `
                <a href="${frontImagePath}" download class="download-btn">Download Front</a>
                <a href="${backImagePath}" download class="download-btn">Download Back</a>
            `;
        }
    } catch (error) {
        console.error('Error:', error);
    } finally {
        document.getElementById('loader').style.display = 'none';
    }
});


        async function generateQR() {
    const content = document.getElementById('qrContent').value;
    if (!content) return;
    
    const formData = new FormData();
    formData.append('content', content);
    formData.append('filename', 'temp_qr');
    
    const initialResponse = await fetch('qr_generator.php', {
        method: 'POST',
        body: formData
    });
    
    if (initialResponse.ok) {
        const data = await initialResponse.json();
        const qrInput = document.querySelector('input[name="qr_code"]');
        
        // Create preview
        const previewDiv = document.getElementById('qrPreview');
        previewDiv.innerHTML = `<img src="${data.qr_file}" style="max-width: 200px;" />`;
        
        // Create a new File object from the generated QR code
        const qrResponse = await fetch(data.qr_file);
        const blob = await qrResponse.blob();
        const file = new File([blob], 'generated_qr.png', { type: 'image/png' });
        
        // Update the file input
        const dataTransfer = new DataTransfer();
        dataTransfer.items.add(file);
        qrInput.files = dataTransfer.files;
    }
}

    </script>
</body>
</html>
