<?php

namespace Tuples\View;

use Tuples\View\Traits\Printers;

class Template
{
    use Printers;

    /**
     * Parent template determined in $t->extends(..) on template
     *
     * @var Template|false
     */
    private Template|false $parent = false;

    /**
     * The slots detected during rendering
     *
     * @var Slot[]|false
     */
    private array|false $slots = false;

    public function __construct(public string $file, public array $data = [])
    {
        if (!file_exists($this->file)) {
            throw new \InvalidArgumentException("file $file doesnt exists");
        }
    }

    /**
     * Designates the template as a child template.
     *
     * This method sets the current template as a child of another template specified by the given file.
     * In the context of using slots (e.g., <x-{slot}>), it's essential to include this tags both in the parent
     * and child templates to establish the correct inheritance and rendering behavior.
     *
     * @param string $file The file path of the parent template.
     * @param array $data An optional array of data to be passed to the parent template.
     * @return void
     */
    public function extends(string $file, array $data = [])
    {
        $this->parent = view($file, $data);
    }

    /**
     * Inserts the rendered content of another template at the point where this method is called.
     *
     * This method creates a new instance of the Template class for the specified file and data,
     * renders the template, and returns the rendered content. The content is then included at the
     * location in the current template where this method is called.
     *
     * @param string $file The file path of the template to be included.
     * @param array $data An optional array of data to be passed to the included template.
     * @return void
     */
    public function include(string $file, array $data = [])
    {
        $template = view($file, $data);
        return $template->render();
    }

    /**
     * Retrieves the rendered content of the template, including its parent structure and slots.
     *
     * This method captures the output buffer, extracts template variables, and includes the template file.
     * The content of the child template, excluding its parent, is obtained and stored. If the child template
     * has a parent, the method merges the content of both, ensuring a complete rendering with slots incorporated.
     *
     * @return string The rendered content of the template with parent structure and slots.
     *
     * @throws \Throwable If an error occurs during the rendering process.
     */
    private function getContent(): string
    {
        try {
            $level = ob_get_level();
            ob_start();

            extract($this->data);
            include $this->file;
            // Content of child-template without parent
            $content = ob_get_clean();

            // If this template has parent, merge the content of both
            if ($this->parent) {
                $parentContent = $this->parent->getContent();
                $content = $this->mergeSlots($parentContent, $content);
            }

            return $content;
        } catch (\Throwable $e) {
            while (ob_get_level() > $level) {
                ob_end_clean();
            }

            throw $e;
        }
    }

    /**
     * Renders the template with cleaned content.
     *
     * This method retrieves the template content, identifies and extracts Slots,
     * and removes those Slots from the final rendered output.
     *
     * @return string The cleaned and optimized rendered template.
     */
    public function render(): string
    {
        $content = $this->getContent();

        // if some slots was redenred (found in child and put in parent), cleanup the tags
        if ($this->slots) {
            $search = [];

            /** @var Slot $slot */
            foreach ($this->slots as $slot) {
                $search[] = $slot->getOpenTag();
                $search[] = $slot->getCloseTag();
            }

            $content = str_replace($search, array_fill(0, count($search), ""), $content);
        }

        return $content;
    }

    /**
     * Merges content of child-slot into parent-slot.
     *
     * This method takes the content of a parent slot and a child slot, identifies matching slots
     * by name, and merges their contents. The resulting content has the child content incorporated
     * into the corresponding parent slot.
     *
     * @param string $parentContent The content of the parent slot.
     * @param string $childContent The content of the child slot to be merged.
     * @return string The merged content with child content incorporated into the parent slot.
     */
    private function mergeSlots(string $parentContent, string $childContent): string
    {
        $renderedSlots = [];

        $parentSlots = $this->getSlots($parentContent);
        $childSlots = $this->getSlots($childContent);

        /** @var Slot $slot */
        foreach ($parentSlots as $name => $slot) {

            if (isset($childSlots[$name])) {

                // Parent Temaplate Slot
                $search[] = $slot->getRaw();

                // Child Template slot
                /** @var Slot $childSlot */
                $childSlot = $childSlots[$name];

                // replace child content to parent slot
                // keep <tag> for chain-extendings
                $replace[] = $slot->getOpenTag() . $slot->getInner() . $childSlot->getInner() . $slot->getCloseTag();

                // store the matched slot between parent and child to further use
                $renderedSlots[] = $childSlots[$name];
            }
        }

        $this->slots = $renderedSlots;

        return str_replace($search, $replace, $parentContent);
    }

    /**
     * Extract Slot instances from the provided content based on a specified string pattern.
     *
     * The Slots are detected using the pattern: <x-name></x-name>
     *
     * @param string $content The input content where Slots are to be detected.
     * @return array An array containing instances of Slot based on the detected pattern.
     */
    private function getSlots(string $content): array
    {
        $slots = [];

        // Obtain all slots (<x-{any}></x-{any}>)
        $pattern = '/<x-(.*?)>(.*?)<\/x-\1>/s';
        preg_match_all($pattern, $content, $matches);

        // Index 0 = Slots RAW => example: <x-style>hola</x-style>
        // Index 1 = Slots NAME => example: style
        // Index 2 = Slots INNER => example: hola
        $slotsCount = count($matches[0]);

        for ($i = 0; $i < $slotsCount; $i++) {
            // Instance the slot with the RAW & the INNER
            $slot = new Slot($matches[1][$i], $matches[0][$i], $matches[2][$i]);
            $name = $matches[1][$i];

            $slots[$name] = $slot;
        }

        return $slots;
    }
}
