<?php
/**
 * Campus2Career – File Upload Handler
 */
class FileUploader {

    private string $uploadDir;
    private array  $allowed   = ['pdf','doc','docx'];
    private int    $maxBytes  = 5 * 1024 * 1024; // 5 MB
    private string $prefix;

    public function __construct(string $uploadDir, string $prefix = 'file') {
        $this->uploadDir = rtrim($uploadDir, '/') . '/';
        $this->prefix = preg_replace('/[^a-z0-9_]/i', '', $prefix) ?: 'file';
        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }
        $index = $this->uploadDir . 'index.php';
        if (!file_exists($index)) {
            file_put_contents($index, "<?php http_response_code(403); exit; ?>");
        }
    }

    /**
     * @return array ['success'=>bool, 'filename'=>string|null, 'error'=>string|null]
     */
    public function upload(array $file): array {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'filename' => null, 'error' => 'Upload error code: ' . $file['error']];
        }
        if ($file['size'] > $this->maxBytes) {
            return ['success' => false, 'filename' => null, 'error' => 'File must be under 5 MB.'];
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $this->allowed, true)) {
            return ['success' => false, 'filename' => null, 'error' => 'Only PDF, DOC, DOCX files are allowed.'];
        }

        // Validate MIME for PDFs
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime  = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        $allowedMimes = [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/zip',
            'application/octet-stream'
        ];
        if (!in_array($mime, $allowedMimes, true)) {
            return ['success' => false, 'filename' => null, 'error' => 'Invalid file content detected.'];
        }

        $newName = uniqid($this->prefix . '_', true) . '.' . $ext;
        $dest    = $this->uploadDir . $newName;

        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            return ['success' => false, 'filename' => null, 'error' => 'Could not save file. Check folder permissions.'];
        }

        return ['success' => true, 'filename' => $newName, 'error' => null];
    }

    public function delete(string $filename): void {
        $path = $this->uploadDir . basename($filename);
        if (file_exists($path)) unlink($path);
    }
}
