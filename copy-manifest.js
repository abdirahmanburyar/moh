import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

// Get current directory
const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

// Source manifest location (where Vite generates it)
const sourceManifestPath = path.join(__dirname, 'public', 'build', '.vite', 'manifest.json');

// Destination manifest location (where Laravel expects it)
const destManifestPath = path.join(__dirname, 'public', 'build', 'manifest.json');

try {
  // Check if source manifest exists
  if (fs.existsSync(sourceManifestPath)) {
    // Read the source manifest
    const manifestContent = fs.readFileSync(sourceManifestPath, 'utf8');
    
    // Write to the destination
    fs.writeFileSync(destManifestPath, manifestContent);
    
    console.log('✅ Manifest successfully copied to:', destManifestPath);
  } else {
    console.error('❌ Source manifest not found at:', sourceManifestPath);
  }
} catch (error) {
  console.error('❌ Error copying manifest:', error);
}
