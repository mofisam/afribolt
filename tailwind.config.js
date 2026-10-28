/** Run with the standalone Tailwind CLI (no Node needed):
 *  ./tailwindcss -i resources/css/input.css -o public/assets/css/tailwind.css --minify --watch
 */
module.exports = {
  content: ['./app/Views/**/*.php', './public/assets/js/**/*.js'],
  theme: {
    extend: {
      colors: {
        bolt: { DEFAULT: '#1B8CCB', dark: '#0F6BA0', tint: '#EAF5FC' },
        ink: '#0A2540',
        spark: '#F26B21',
        volt: '#FFC800',
      },
      fontFamily: {
        display: ['Raleway', 'system-ui', 'sans-serif'],
        sans: ['"Nunito Sans"', 'system-ui', 'sans-serif'],
      },
    },
  },
};
