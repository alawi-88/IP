module.exports = {
    apps: [
      {
        name: "INNOVATION-STG",
        exec_mode: "fork",
        instances: 1,
        script: "node_modules/next/dist/bin/next",
        args: "start",
        env: {
          PORT: 3000,
        }
      },
    ],
  };
