"use client";

import { Modal, Form, Button, Select } from "antd";
import { FormInstance, useForm } from "antd/es/form/Form";
import { useTranslations } from "next-intl";
import { useState } from "react";
import { FiFilter } from "react-icons/fi";

export default function FilterResultsModal({
  form,
  filterResults,
  children,
}: {
  form: FormInstance<any>;
  filterResults: (values: any) => void;
  children: React.ReactNode;
}) {
  const t = useTranslations();
  const [openModal, setOpenModal] = useState(false);

  const onSubmit = (values: any) => {
    filterResults(values);
    setOpenModal(false);
  };

  return (
    <>
      <Button onClick={() => setOpenModal(true)}>
        <FiFilter />
        {t("filter-results")}
      </Button>
      <Modal
        footer={null}
        onCancel={() => setOpenModal(false)}
        className="w-full max-w-[540px]"
        open={openModal}
      >
        <div className="flex flex-col gap-y-10 py-6 px-4">
          <h1 className="text-[#5B656A] text-2xl font-bold m-0">
            {t("filter-results")}
          </h1>
          <Form
            layout="vertical"
            onFinish={onSubmit}
            form={form}
            className=""
          >
            {children}

            <Button
              type="primary"
              htmlType="submit"
              className="w-full"
            >
              {t("show-the-results")}
            </Button>
          </Form>
        </div>
      </Modal>
    </>
  );
}
