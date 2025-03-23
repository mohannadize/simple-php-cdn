# PHP Image CDN

A powerful and secure PHP-based Content Delivery Network (CDN) for dynamic image hosting and processing. This CDN provides real-time image resizing, quality optimization, and caching capabilities with a simple API interface.

## Features

- 🚀 Fast image processing and serving
- 🔒 Secure file validation and storage
- 🖼️ Dynamic image resizing and quality adjustment
- 💾 Automatic caching of processed images
- 🔑 API authentication support
- 📦 Support for multiple image formats (JPG, PNG, GIF, WebP)
- 🛡️ Protection against common security vulnerabilities
- 📊 Built-in rate limiting and file size restrictions
- 🔄 UUID-based file naming for security
- 📝 Detailed error logging and debugging tools
- 🌐 Support for uploading images from URLs

## Requirements

- PHP 7.3 or higher
- Composer
- GD or Imagick PHP extension
- Apache/Nginx web server
- Write permissions for upload and cache directories

## Installation

1. Clone the repository:
   ```bash
   git clone https://github.com/yourusername/php-cdn.git
   cd php-cdn
   ```

2. Install dependencies:
   ```bash
   composer install
   ```

3. Create and configure your environment file:
   ```bash
   cp .env.example .env
   ```

4. Configure your `.env` file with appropriate values:
   ```env
   APP_NAME=PHP-CDN
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://your-domain.com
   
   UPLOAD_DIR=uploads
   PROCESSED_DIR=images
   MAX_FILE_SIZE=10485760
   ALLOWED_EXTENSIONS=jpg,jpeg,png,gif,webp
   
   PRIVATE_KEY=your-secret-key-here
   
   DEFAULT_QUALITY=80
   DEFAULT_WIDTH=800
   MAX_WIDTH=2000
   ```

5. Set up directory permissions:
   ```bash
   chmod -R 755 public/uploads
   chmod -R 755 public/images
   ```

## API Documentation

### Authentication

All upload requests require authentication using a Bearer token. Include your private key in the Authorization header:

```http
Authorization: Bearer your-secret-key-here
```

### Upload an Image

**Endpoint:** `POST /upload`

**Headers:**
```http
Content-Type: multipart/form-data
Authorization: Bearer your-secret-key-here
```

**Form Data:**
- `image`: The image file to upload

**Response:**
```json
{
  "success": true,
  "filename": "9a5c8f63-2d3e-4d6f-b2e1-1a6e7b8c9d0e.jpg",
  "url": "https://your-domain.com/image/9a5c8f63-2d3e-4d6f-b2e1-1a6e7b8c9d0e.jpg",
  "usage": {
    "original": "https://your-domain.com/image/9a5c8f63-2d3e-4d6f-b2e1-1a6e7b8c9d0e.jpg",
    "resize": "https://your-domain.com/image/9a5c8f63-2d3e-4d6f-b2e1-1a6e7b8c9d0e.jpg?w=500",
    "quality": "https://your-domain.com/image/9a5c8f63-2d3e-4d6f-b2e1-1a6e7b8c9d0e.jpg?q=75",
    "both": "https://your-domain.com/image/9a5c8f63-2d3e-4d6f-b2e1-1a6e7b8c9d0e.jpg?w=500&q=75"
  }
}
```

### Upload from URL

**Endpoint:** `POST /upload-url`

**Headers:**
```http
Content-Type: application/json
Authorization: Bearer your-secret-key-here
```

**Request Body:**
```json
{
  "url": "https://example.com/path/to/image.jpg"
}
```

**Response:**
```json
{
  "success": true,
  "filename": "9a5c8f63-2d3e-4d6f-b2e1-1a6e7b8c9d0e.jpg",
  "url": "https://your-domain.com/image/9a5c8f63-2d3e-4d6f-b2e1-1a6e7b8c9d0e.jpg",
  "usage": {
    "original": "https://your-domain.com/image/9a5c8f63-2d3e-4d6f-b2e1-1a6e7b8c9d0e.jpg",
    "resize": "https://your-domain.com/image/9a5c8f63-2d3e-4d6f-b2e1-1a6e7b8c9d0e.jpg?w=500",
    "quality": "https://your-domain.com/image/9a5c8f63-2d3e-4d6f-b2e1-1a6e7b8c9d0e.jpg?q=75",
    "both": "https://your-domain.com/image/9a5c8f63-2d3e-4d6f-b2e1-1a6e7b8c9d0e.jpg?w=500&q=75"
  }
}
```

### Serve an Image

**Endpoint:** `GET /image/{filename}`

**Query Parameters:**
- `w` (optional): Desired width in pixels (default: 800)
- `q` (optional): JPEG quality 1-100 (default: 80)

**Examples:**
```
# Original image
https://your-domain.com/image/9a5c8f63-2d3e-4d6f-b2e1-1a6e7b8c9d0e.jpg

# Resized to 500px width
https://your-domain.com/image/9a5c8f63-2d3e-4d6f-b2e1-1a6e7b8c9d0e.jpg?w=500

# Compressed to 75% quality
https://your-domain.com/image/9a5c8f63-2d3e-4d6f-b2e1-1a6e7b8c9d0e.jpg?q=75

# Both resized and compressed
https://your-domain.com/image/9a5c8f63-2d3e-4d6f-b2e1-1a6e7b8c9d0e.jpg?w=500&q=75
```

## Integration Examples

### HTML Image Tag with Responsive Srcset

```html
<img src="https://your-domain.com/image/9a5c8f63-2d3e-4d6f-b2e1-1a6e7b8c9d0e.jpg?w=800&q=80" 
     srcset="https://your-domain.com/image/9a5c8f63-2d3e-4d6f-b2e1-1a6e7b8c9d0e.jpg?w=400&q=80 400w,
             https://your-domain.com/image/9a5c8f63-2d3e-4d6f-b2e1-1a6e7b8c9d0e.jpg?w=800&q=80 800w,
             https://your-domain.com/image/9a5c8f63-2d3e-4d6f-b2e1-1a6e7b8c9d0e.jpg?w=1200&q=80 1200w"
     sizes="(max-width: 600px) 400px, (max-width: 1200px) 800px, 1200px"
     alt="Responsive image">
```

### JavaScript Upload Example

```javascript
// Example image upload with authentication
async function uploadImage(file) {
  const formData = new FormData();
  formData.append('image', file);

  try {
    const response = await fetch('https://your-domain.com/upload', {
      method: 'POST',
      headers: {
        'Authorization': 'Bearer your-secret-key-here'
      },
      body: formData
    });

    const data = await response.json();
    if (data.success) {
      console.log('Image uploaded successfully:', data.url);
      return data;
    } else {
      throw new Error(data.error || 'Upload failed');
    }
  } catch (error) {
    console.error('Upload error:', error);
    throw error;
  }
}

// Example URL upload with authentication
async function uploadFromUrl(imageUrl) {
  try {
    const response = await fetch('https://your-domain.com/upload-url', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': 'Bearer your-secret-key-here'
      },
      body: JSON.stringify({ url: imageUrl })
    });

    const data = await response.json();
    if (data.success) {
      console.log('Image uploaded successfully:', data.url);
      return data;
    } else {
      throw new Error(data.error || 'Upload failed');
    }
  } catch (error) {
    console.error('URL upload error:', error);
    throw error;
  }
}
```

### PHP Integration Example

```php
<?php
// Example image upload using PHP cURL
function uploadImage($imagePath, $apiKey) {
    $curl = curl_init();
    
    curl_setopt_array($curl, [
        CURLOPT_URL => 'https://your-domain.com/upload',
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $apiKey
        ],
        CURLOPT_POSTFIELDS => [
            'image' => new CURLFile($imagePath)
        ]
    ]);
    
    $response = curl_exec($curl);
    $error = curl_error($curl);
    curl_close($curl);
    
    if ($error) {
        throw new Exception('Upload failed: ' . $error);
    }
    
    return json_decode($response, true);
}

// Example URL upload using PHP cURL
function uploadFromUrl($imageUrl, $apiKey) {
    $curl = curl_init();
    
    curl_setopt_array($curl, [
        CURLOPT_URL => 'https://your-domain.com/upload-url',
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey
        ],
        CURLOPT_POSTFIELDS => json_encode([
            'url' => $imageUrl
        ])
    ]);
    
    $response = curl_exec($curl);
    $error = curl_error($curl);
    curl_close($curl);
    
    if ($error) {
        throw new Exception('URL upload failed: ' . $error);
    }
    
    return json_decode($response, true);
}
```

## Server Configuration

### Apache Configuration

Ensure your Apache configuration includes these modules:
```bash
sudo a2enmod rewrite
sudo a2enmod headers
```

The included `.htaccess` files handle:
- URL rewriting
- Security headers
- Cache control
- File upload limits
- Directory access restrictions

### Nginx Configuration

Example Nginx server block:
```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /path/to/php-cdn/public;
    
    client_max_body_size 20M;
    
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
    
    location ~* \.(jpg|jpeg|png|gif|webp)$ {
        expires max;
        add_header Cache-Control "public, max-age=31536000, immutable";
    }
    
    location ~ /\. {
        deny all;
    }
}
```

## Security Considerations

1. **API Authentication**
   - All upload requests require a valid API key
   - Keys should be kept secure and rotated regularly
   - Use HTTPS in production

2. **File Validation**
   - Strict file type checking (MIME and extension)
   - Maximum file size limits
   - Secure file naming using UUIDs

3. **Directory Security**
   - Protected upload and cache directories
   - No direct access to source files
   - Restricted file permissions

4. **Error Handling**
   - Detailed error logging
   - Sanitized error messages in production
   - Rate limiting on API endpoints

## Debugging Tools

The CDN includes several debugging tools:

1. **Direct Link Test**
   ```
   /direct-link.php?file=your-image.jpg
   ```

2. **Processing Test**
   ```
   /test-process.php?file=your-image.jpg&width=500&quality=80
   ```

3. **Simple Test Interface**
   ```
   /upload.html
   ```

## Configuration Options

All configuration options can be set in your `.env` file:

| Option | Description | Default |
|--------|-------------|---------|
| `APP_NAME` | Application name | PHP-CDN |
| `APP_ENV` | Environment (production/development) | production |
| `APP_DEBUG` | Enable debug mode | false |
| `APP_URL` | Base URL for the CDN | http://localhost |
| `UPLOAD_DIR` | Directory for original uploads | uploads |
| `PROCESSED_DIR` | Directory for processed images | images |
| `MAX_FILE_SIZE` | Maximum upload size in bytes | 10485760 |
| `ALLOWED_EXTENSIONS` | Allowed file extensions | jpg,jpeg,png,gif,webp |
| `PRIVATE_KEY` | API authentication key | null |
| `DEFAULT_QUALITY` | Default JPEG quality | 80 |
| `DEFAULT_WIDTH` | Default resize width | 800 |
| `MAX_WIDTH` | Maximum allowed width | 2000 |

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request. 