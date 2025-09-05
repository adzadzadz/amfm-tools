const esbuild = require('esbuild');
const path = require('path');
const fs = require('fs');

const files = [
  { input: 'admin/js/amfm-bylines-admin.js', output: 'admin/js/amfm-bylines-admin.min.js' },
  { input: 'public/js/amfm-bylines-public.js', output: 'public/js/amfm-bylines-public.min.js' },
  { input: 'public/js/amfm-elementor-widgets.js', output: 'public/js/amfm-elementor-widgets.min.js' },
  { input: 'admin/css/amfm-bylines-admin.css', output: 'admin/css/amfm-bylines-admin.min.css' },
  { input: 'public/css/amfm-bylines-public.css', output: 'public/css/amfm-bylines-public.min.css' }
];

async function build() {
  try {
    for (const file of files) {
      await esbuild.build({
        entryPoints: [file.input],
        bundle: false,
        minify: true,
        sourcemap: false,
        outfile: file.output,
        loader: {
          '.css': 'css'
        }
      });
      console.log(`✅ Minified: ${file.input} → ${file.output}`);
    }
    console.log('✅ All files minified successfully!');
  } catch (error) {
    console.error('❌ Build failed:', error);
    process.exit(1);
  }
}

if (require.main === module) {
  build();
}

module.exports = { build, files };