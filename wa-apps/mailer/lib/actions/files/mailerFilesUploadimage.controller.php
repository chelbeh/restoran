<?php

/**
 * Upload image using WYSIWYG toolbar button.
 */
class mailerFilesUploadimageController extends waUploadJsonController
{
    protected $name;

    protected function process()
    {
        $f = waRequest::file('file');
        $this->name = $f->name;
        if ($this->processFile($f)) {
            $message_id = waRequest::request('message_id');
            if ($message_id) {
                $this->response = wa()->getDataUrl('files/'.$message_id.'/'.$this->name, true, 'mailer', true);
            } else {
                $this->response = wa()->getDataUrl('files/'.$this->name, true, 'mailer', true);
            }
            $this->response = str_replace('https://', 'http://', $this->response);
        }
    }

    public function display()
    {
        $this->getResponse()->sendHeaders();
        if (!$this->errors) {
            if (waRequest::get('filelink')) {
                echo stripslashes(json_encode(array('filelink' => $this->response)));
            } else {
                $data = array('status' => 'ok', 'data' => $this->response);
                echo json_encode($data);
            }
        } else {
            if (waRequest::get('filelink')) {
                echo json_encode(array('error' => $this->errors));
            } else {
                echo json_encode(array('status' => 'fail', 'errors' => $this->errors));
            }
        }
    }

    protected function getPath()
    {
        $message_id = waRequest::request('message_id');
        if ($message_id) {
            return wa()->getDataPath('files/'.$message_id, true);
        } else {
            return wa()->getDataPath('files', true);
        }
    }

    protected function isValid($f)
    {
        $allowed = array('jpg', 'jpeg', 'png', 'gif');
        if (!in_array(strtolower($f->extension), $allowed)) {
            $this->errors[] = sprintf(_w("Files with extensions %s are allowed only."), '*.'.implode(', *.', $allowed));
            return false;
        }
        return true;
    }

    protected function save(waRequestFile $f)
    {
        if (file_exists($this->path.DIRECTORY_SEPARATOR.$f->name)) {
            $i = strrpos($f->name, '.');
            $name = substr($f->name, 0, $i);
            $ext = substr($f->name, $i + 1);
            $i = 1;
            while (file_exists($this->path.DIRECTORY_SEPARATOR.$name.'-'.$i.'.'.$ext)) {
                $i++;
            }
            $this->name = $name.'-'.$i.'.'.$ext;
            return $f->moveTo($this->path, $this->name);
        }
        return $f->moveTo($this->path, $f->name);
    }
}