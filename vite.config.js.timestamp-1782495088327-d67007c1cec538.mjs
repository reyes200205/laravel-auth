// vite.config.js
import { defineConfig } from "file:///C:/laragon/www/laravel-auth/node_modules/vite/dist/node/index.js";
import laravel from "file:///C:/laragon/www/laravel-auth/node_modules/laravel-vite-plugin/dist/index.js";
import vue from "file:///C:/laragon/www/laravel-auth/node_modules/@vitejs/plugin-vue/dist/index.mjs";
import fs from "fs";
var host = "auth-laravel.test";
var serverConfig = {};
if (fs.existsSync("C:/laragon/etc/ssl/laragon.crt") && fs.existsSync("C:/laragon/etc/ssl/laragon.key")) {
  serverConfig = {
    host: "0.0.0.0",
    port: 5173,
    https: {
      key: fs.readFileSync("C:/laragon/etc/ssl/laragon.key"),
      cert: fs.readFileSync("C:/laragon/etc/ssl/laragon.crt")
    },
    hmr: {
      host
    }
  };
}
var vite_config_default = defineConfig({
  server: serverConfig,
  plugins: [
    laravel({
      input: "resources/js/app.js",
      refresh: true
    }),
    vue({
      template: {
        transformAssetUrls: {
          base: null,
          includeAbsolute: false
        }
      }
    })
  ]
});
export {
  vite_config_default as default
};
//# sourceMappingURL=data:application/json;base64,ewogICJ2ZXJzaW9uIjogMywKICAic291cmNlcyI6IFsidml0ZS5jb25maWcuanMiXSwKICAic291cmNlc0NvbnRlbnQiOiBbImNvbnN0IF9fdml0ZV9pbmplY3RlZF9vcmlnaW5hbF9kaXJuYW1lID0gXCJDOlxcXFxsYXJhZ29uXFxcXHd3d1xcXFxsYXJhdmVsLWF1dGhcIjtjb25zdCBfX3ZpdGVfaW5qZWN0ZWRfb3JpZ2luYWxfZmlsZW5hbWUgPSBcIkM6XFxcXGxhcmFnb25cXFxcd3d3XFxcXGxhcmF2ZWwtYXV0aFxcXFx2aXRlLmNvbmZpZy5qc1wiO2NvbnN0IF9fdml0ZV9pbmplY3RlZF9vcmlnaW5hbF9pbXBvcnRfbWV0YV91cmwgPSBcImZpbGU6Ly8vQzovbGFyYWdvbi93d3cvbGFyYXZlbC1hdXRoL3ZpdGUuY29uZmlnLmpzXCI7aW1wb3J0IHsgZGVmaW5lQ29uZmlnIH0gZnJvbSAndml0ZSc7XG5pbXBvcnQgbGFyYXZlbCBmcm9tICdsYXJhdmVsLXZpdGUtcGx1Z2luJztcbmltcG9ydCB2dWUgZnJvbSAnQHZpdGVqcy9wbHVnaW4tdnVlJztcbmltcG9ydCBmcyBmcm9tICdmcyc7XG5cbi8vIENvbmZpZ3VyYXRpb24gZm9yIGxvY2FsIEhUVFBTIHdpdGggTGFyYWdvblxuY29uc3QgaG9zdCA9ICdhdXRoLWxhcmF2ZWwudGVzdCc7XG5sZXQgc2VydmVyQ29uZmlnID0ge307XG5cbmlmIChmcy5leGlzdHNTeW5jKCdDOi9sYXJhZ29uL2V0Yy9zc2wvbGFyYWdvbi5jcnQnKSAmJiBmcy5leGlzdHNTeW5jKCdDOi9sYXJhZ29uL2V0Yy9zc2wvbGFyYWdvbi5rZXknKSkge1xuICAgIHNlcnZlckNvbmZpZyA9IHtcbiAgICAgICAgaG9zdDogJzAuMC4wLjAnLFxuICAgICAgICBwb3J0OiA1MTczLFxuICAgICAgICBodHRwczoge1xuICAgICAgICAgICAga2V5OiBmcy5yZWFkRmlsZVN5bmMoJ0M6L2xhcmFnb24vZXRjL3NzbC9sYXJhZ29uLmtleScpLFxuICAgICAgICAgICAgY2VydDogZnMucmVhZEZpbGVTeW5jKCdDOi9sYXJhZ29uL2V0Yy9zc2wvbGFyYWdvbi5jcnQnKSxcbiAgICAgICAgfSxcbiAgICAgICAgaG1yOiB7XG4gICAgICAgICAgICBob3N0OiBob3N0LFxuICAgICAgICB9LFxuICAgIH07XG59XG5cbmV4cG9ydCBkZWZhdWx0IGRlZmluZUNvbmZpZyh7XG4gICAgc2VydmVyOiBzZXJ2ZXJDb25maWcsXG4gICAgcGx1Z2luczogW1xuICAgICAgICBsYXJhdmVsKHtcbiAgICAgICAgICAgIGlucHV0OiAncmVzb3VyY2VzL2pzL2FwcC5qcycsXG4gICAgICAgICAgICByZWZyZXNoOiB0cnVlLFxuICAgICAgICB9KSxcbiAgICAgICAgdnVlKHtcbiAgICAgICAgICAgIHRlbXBsYXRlOiB7XG4gICAgICAgICAgICAgICAgdHJhbnNmb3JtQXNzZXRVcmxzOiB7XG4gICAgICAgICAgICAgICAgICAgIGJhc2U6IG51bGwsXG4gICAgICAgICAgICAgICAgICAgIGluY2x1ZGVBYnNvbHV0ZTogZmFsc2UsXG4gICAgICAgICAgICAgICAgfSxcbiAgICAgICAgICAgIH0sXG4gICAgICAgIH0pLFxuICAgIF0sXG59KTtcblxuIl0sCiAgIm1hcHBpbmdzIjogIjtBQUEyUSxTQUFTLG9CQUFvQjtBQUN4UyxPQUFPLGFBQWE7QUFDcEIsT0FBTyxTQUFTO0FBQ2hCLE9BQU8sUUFBUTtBQUdmLElBQU0sT0FBTztBQUNiLElBQUksZUFBZSxDQUFDO0FBRXBCLElBQUksR0FBRyxXQUFXLGdDQUFnQyxLQUFLLEdBQUcsV0FBVyxnQ0FBZ0MsR0FBRztBQUNwRyxpQkFBZTtBQUFBLElBQ1gsTUFBTTtBQUFBLElBQ04sTUFBTTtBQUFBLElBQ04sT0FBTztBQUFBLE1BQ0gsS0FBSyxHQUFHLGFBQWEsZ0NBQWdDO0FBQUEsTUFDckQsTUFBTSxHQUFHLGFBQWEsZ0NBQWdDO0FBQUEsSUFDMUQ7QUFBQSxJQUNBLEtBQUs7QUFBQSxNQUNEO0FBQUEsSUFDSjtBQUFBLEVBQ0o7QUFDSjtBQUVBLElBQU8sc0JBQVEsYUFBYTtBQUFBLEVBQ3hCLFFBQVE7QUFBQSxFQUNSLFNBQVM7QUFBQSxJQUNMLFFBQVE7QUFBQSxNQUNKLE9BQU87QUFBQSxNQUNQLFNBQVM7QUFBQSxJQUNiLENBQUM7QUFBQSxJQUNELElBQUk7QUFBQSxNQUNBLFVBQVU7QUFBQSxRQUNOLG9CQUFvQjtBQUFBLFVBQ2hCLE1BQU07QUFBQSxVQUNOLGlCQUFpQjtBQUFBLFFBQ3JCO0FBQUEsTUFDSjtBQUFBLElBQ0osQ0FBQztBQUFBLEVBQ0w7QUFDSixDQUFDOyIsCiAgIm5hbWVzIjogW10KfQo=
