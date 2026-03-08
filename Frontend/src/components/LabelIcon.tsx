import React from "react";
export default function LabelIcon({ icon }: { icon: React.ReactNode }) {
  const iconWithClass = React.isValidElement(icon)
    ? // @ts-expect-error -- TypeScript will validate that `Icon` is a valid
      React.cloneElement(icon, { className: "opacity-50 " })
    : icon;

  return iconWithClass;
}
