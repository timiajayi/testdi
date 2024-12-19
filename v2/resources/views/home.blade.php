<!DOCTYPE html>
<html>

<head>
    <title>ID Card Generator</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/croppie/2.6.5/croppie.min.css">
    <link rel="stylesheet" href="{{ asset('css/home.css') }}">
</head>

<body>
    <div class="company-header">
        <img src="{{ asset('templates/logo.png') }}" alt="Company Logo" class="company-logo">
        <h1>ID Card Generator</h1>
    </div>
    <div class="nav-links">
    <a href="{{ route('gallery') }}" class="nav-link">View ID Card Gallery</a>
    @if(auth()->user()->is_admin)
        <a href="{{ route('admin.dashboard') }}" class="nav-link">Admin Dashboard</a>
    @endif
    <form action="{{ route('logout') }}" method="POST" style="display: inline;">
        @csrf
        <button type="submit" class="nav-link" style="border: none; cursor: pointer;">Logout</button>
    </form>
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
            <!-- <div class="form-group">
                <label>Generate QR Code Content (Optional)</label>
                <textarea id="qrContent" placeholder="Enter content for QR code generation"></textarea>
                <button type="button" onclick="generateQR()" class="secondary-btn" style="margin-top: 10px;">Generate QR Code</button>
                <div id="qrPreview" style="margin-top: 10px; text-align: center;"></div>
            </div> -->
            <div class="form-group">
                <h3>Contact Information for QR Code</h3>
                <div class="qr-fields">
                    <label>First Name:</label>
                    <input type="text" id="firstName" placeholder="First Name">

                    <label>Last Name:</label>
                    <input type="text" id="lastName" placeholder="Last Name">

                    <label>Company:</label>
                    <input type="text" id="company" placeholder="Company/Organization">

                    <label>Job Title:</label>
                    <input type="text" id="jobTitle" placeholder="Job Title">

                    <label>Mobile:</label>
                    <input type="text" id="mobile" placeholder="Mobile Number">

                    <label>Phone:</label>
                    <input type="text" id="phone" placeholder="Phone Number">

                    <label>Email:</label>
                    <input type="email" id="email" placeholder="Email Address">

                    <label>Website:</label>
                    <input type="url" id="website" placeholder="Website URL">

                    <label>Address:</label>
                    <input type="text" id="street" placeholder="Street Address">

                    <label>City:</label>
                    <input type="text" id="city" placeholder="City">

                    <label>State:</label>
                    <input type="text" id="state" placeholder="State">

                    <label>Country:</label>
                    <input type="text" id="country" placeholder="Country">

                    <label>Birthday:</label>
                    <input type="date" id="birthday" placeholder="Birthday">
                </div>
                <button type="button" onclick="generateContactQR()" class="secondary-btn">Generate Contact QR Code</button>
                <div id="qrPreview" style="margin-top: 10px; text-align: center;"></div>
            </div>



            <button type="submit">Generate ID Card</button>
        </form>
        <div class="nav-links">
            <a href="{{ route('gallery') }}" class="nav-link">View ID Card Gallery</a>
            <form action="{{ route('logout') }}" method="POST" style="display: inline;">
                @csrf
                <button type="submit" class="nav-link" style="border: none; cursor: pointer;">Logout</button>
            </form>
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
                        viewport: {
                            width: 307,
                            height: 307,
                            type: 'circle'
                        },
                        boundary: {
                            width: 400,
                            height: 400
                        },
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
                size: {
                    width: 307,
                    height: 307
                },
                format: 'jpeg',
                quality: 1,
                circle: true
            }).then(function(blob) {
                const croppedFile = new File([blob], "profile.jpg", {
                    type: "image/jpeg"
                });

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
                const response = await fetch('{{ route("generate") }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    document.getElementById('frontPreview').src = data.front_image;
                    document.getElementById('backPreview').src = data.back_image;

                    document.getElementById('downloadButtons').innerHTML = `
                <a href="${data.front_image}" download class="download-btn">Download Front</a>
                <a href="${data.back_image}" download class="download-btn">Download Back</a>
            `;
                }
            } catch (error) {
                console.error('Error:', error);
            } finally {
                document.getElementById('loader').style.display = 'none';
            }
        });

        function generateContactQR() {
            const formData = new FormData();

            const fields = {
                'firstName': document.getElementById('firstName').value,
                'lastName': document.getElementById('lastName').value,
                'company': document.getElementById('company').value,
                'jobTitle': document.getElementById('jobTitle').value,
                'mobile': document.getElementById('mobile').value,
                'phone': document.getElementById('phone').value,
                'email': document.getElementById('email').value,
                'website': document.getElementById('website').value,
                'street': document.getElementById('street').value,
                'city': document.getElementById('city').value,
                'state': document.getElementById('state').value,
                'country': document.getElementById('country').value,
                'birthday': document.getElementById('birthday').value
            };

            Object.keys(fields).forEach(key => {
                formData.append(key, fields[key]);
            });

            fetch('{{ route("generate.qr") }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const previewDiv = document.getElementById('qrPreview');
                        previewDiv.innerHTML = `<img src="${data.qr_url}" style="max-width: 200px;" />`;

                        const qrInput = document.querySelector('input[name="qr_code"]');
                        fetch(data.qr_url)
                            .then(response => response.blob())
                            .then(blob => {
                                const file = new File([blob], 'contact_qr.png', {
                                    type: 'image/png'
                                });
                                const dataTransfer = new DataTransfer();
                                dataTransfer.items.add(file);
                                qrInput.files = dataTransfer.files;
                            });
                    }
                });
        }


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
                const file = new File([blob], 'generated_qr.png', {
                    type: 'image/png'
                });

                // Update the file input
                const dataTransfer = new DataTransfer();
                dataTransfer.items.add(file);
                qrInput.files = dataTransfer.files;
            }
        }
    </script>
</body>

</html>