import { Empty as AntdEmpty } from "antd";
import ErrorSvg from "./feedback-modal/_svgs/error";

export default function Empty({
  description,
  children,
  isNotification,
  customImage,
}: {
  description: string | React.ReactNode;
  children?: React.ReactNode;
  isNotification?: boolean;
  customImage?: string | React.ReactNode;
}) {
  return (
    <AntdEmpty
      image={
        customImage
          ? customImage
          : isNotification
          ? "/notification.svg"
          : <ErrorSvg/>
      }
      description={description}
    >
      {children}
    </AntdEmpty>
  );
}
