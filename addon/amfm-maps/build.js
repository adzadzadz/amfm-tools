const esbuild = require('esbuild');
const fs = require('fs');
const path = require('path');

// Define asset directories
const assetDirs = [
  'admin/js',
  'admin/css', 
  'assets/js',
  'assets/css'
];

// Minify JS files
async function minifyJS(filePath) {
  try {
    const dir = path.dirname(filePath);
    const name = path.basename(filePath, '.js');
    const minFilePath = path.join(dir, `${name}.min.js`);
    
    await esbuild.build({
      entryPoints: [filePath],
      outfile: minFilePath,
      minify: true,
      format: 'iife',
      target: 'es2015'
    });
    console.log(`✓ Created: ${minFilePath}`);
  } catch (error) {
    console.error(`✗ Error minifying ${filePath}:`, error);
  }
}

// Minify CSS files
async function minifyCSS(filePath) {
  try {
    const dir = path.dirname(filePath);
    const name = path.basename(filePath, '.css');
    const minFilePath = path.join(dir, `${name}.min.css`);
    
    await esbuild.build({
      entryPoints: [filePath],
      outfile: minFilePath,
      minify: true,
      loader: { '.css': 'css' }
    });
    console.log(`✓ Created: ${minFilePath}`);
  } catch (error) {
    console.error(`✗ Error minifying ${filePath}:`, error);
  }
}

// Process all assets
async function minifyAssets() {
  console.log('🔧 Starting asset minification...\n');
  
  for (const dir of assetDirs) {
    if (!fs.existsSync(dir)) {
      console.log(`⚠️  Directory ${dir} not found, skipping...`);
      continue;
    }
    
    const files = fs.readdirSync(dir);
    
    for (const file of files) {
      const filePath = path.join(dir, file);
      const ext = path.extname(file);
      
      // Skip already minified files
      if (file.includes('.min.')) {
        continue;
      }
      
      if (ext === '.js') {
        await minifyJS(filePath);
      } else if (ext === '.css') {
        await minifyCSS(filePath);
      }
    }
  }
  
  console.log('\n🎉 Asset minification complete!');
}

minifyAssets().catch(console.error);